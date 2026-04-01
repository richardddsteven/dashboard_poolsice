import 'dart:async';
import 'dart:convert';

import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:http/http.dart' as http;

String get apiBaseUrl {
  if (kIsWeb) {
    return 'http://127.0.0.1:8000/api';
  }

  if (defaultTargetPlatform == TargetPlatform.android) {
    return 'http://10.0.2.2:8000/api';
  }

  return 'http://127.0.0.1:8000/api';
}

final FlutterLocalNotificationsPlugin localNotifications =
    FlutterLocalNotificationsPlugin();

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await _initNotifications();
  runApp(const DriverApp());
}

Future<void> _initNotifications() async {
  const androidSettings = AndroidInitializationSettings('@mipmap/ic_launcher');
  const initSettings = InitializationSettings(android: androidSettings);

  await localNotifications.initialize(initSettings);

  await localNotifications
      .resolvePlatformSpecificImplementation<
        AndroidFlutterLocalNotificationsPlugin
      >()
      ?.requestNotificationsPermission();
}

class DriverApp extends StatelessWidget {
  const DriverApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      debugShowCheckedModeBanner: false,
      title: 'Driver Notifier',
      theme: ThemeData(
        colorScheme: ColorScheme.fromSeed(seedColor: const Color(0xFF0B6E4F)),
        useMaterial3: true,
      ),
      home: const LoginScreen(),
    );
  }
}

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _usernameController = TextEditingController();
  final _passwordController = TextEditingController();
  bool _isSubmitting = false;

  @override
  void dispose() {
    _usernameController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  Future<void> _submitLogin() async {
    if (_isSubmitting) {
      return;
    }

    final username = _usernameController.text.trim();
    final password = _passwordController.text;

    if (username.isEmpty || password.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Username dan password wajib diisi.')),
      );
      return;
    }

    setState(() {
      _isSubmitting = true;
    });

    try {
      final uri = Uri.parse('$apiBaseUrl/driver/login');
      final response = await http.post(
        uri,
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({
          'username': username,
          'password': password,
        }),
      );

      if (response.statusCode != 200) {
        final message = _extractErrorMessage(response.body);
        throw Exception(message);
      }

      final payload = jsonDecode(response.body) as Map<String, dynamic>;
      final data = payload['data'] as Map<String, dynamic>?;

      if (data == null) {
        throw Exception('Respons login tidak valid.');
      }

      final driverId = data['driver_id'] as int?;
      final zone = (data['zone'] as String? ?? '').trim();
      final driverName = (data['driver_name'] as String? ?? '').trim();
      final safeDriverName = driverName.isEmpty ? 'Supir #$driverId' : driverName;

      if (driverId == null || zone.isEmpty) {
        throw Exception('Data supir belum lengkap.');
      }

      if (!mounted) {
        return;
      }

      Navigator.push(
        context,
        MaterialPageRoute(
          builder: (_) => DriverHomeScreen(
            driverId: driverId,
            driverName: safeDriverName,
            zone: zone,
          ),
        ),
      );
    } catch (e) {
      if (!mounted) {
        return;
      }
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Login gagal: $e')),
      );
    } finally {
      if (mounted) {
        setState(() {
          _isSubmitting = false;
        });
      }
    }
  }

  String _extractErrorMessage(String body) {
    try {
      final payload = jsonDecode(body) as Map<String, dynamic>;
      final message = payload['message'] as String?;
      if (message != null && message.trim().isNotEmpty) {
        return message;
      }
      return 'Username atau password salah.';
    } catch (_) {
      return 'Username atau password salah.';
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Masuk Supir')),
      body: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            TextField(
              controller: _usernameController,
              decoration: const InputDecoration(
                labelText: 'Username',
                border: OutlineInputBorder(),
              ),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _passwordController,
              obscureText: true,
              decoration: const InputDecoration(
                labelText: 'Password',
                border: OutlineInputBorder(),
              ),
            ),
            const SizedBox(height: 16),
            SizedBox(
              width: double.infinity,
              child: FilledButton(
                onPressed: _isSubmitting ? null : _submitLogin,
                child: Text(_isSubmitting ? 'Memproses...' : 'Login Supir'),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class DriverHomeScreen extends StatefulWidget {
  const DriverHomeScreen({
    super.key,
    required this.driverId,
    required this.driverName,
    required this.zone,
  });

  final int driverId;
  final String driverName;
  final String zone;

  @override
  State<DriverHomeScreen> createState() => _DriverHomeScreenState();
}

class _DriverHomeScreenState extends State<DriverHomeScreen> {
  final List<Map<String, dynamic>> _orders = [];
  final Set<int> _notifiedOrderIds = <int>{};
  Timer? _timer;
  bool _isOnline = true;
  bool _isLoading = false;
  final Set<int> _isUpdatingOrderIds = <int>{};

  @override
  void initState() {
    super.initState();
    _startPolling();
  }

  @override
  void dispose() {
    _timer?.cancel();
    super.dispose();
  }

  void _startPolling() {
    _timer?.cancel();
    _fetchOrders();
    _timer = Timer.periodic(const Duration(seconds: 10), (_) {
      if (_isOnline) {
        _fetchOrders();
      }
    });
  }

  Future<void> _fetchOrders() async {
    if (_isLoading) {
      return;
    }

    setState(() {
      _isLoading = true;
    });

    try {
      final uri = Uri.parse(
        '$apiBaseUrl/driver/orders/notifications'
        '?driver_id=${widget.driverId}'
        '&zone=${Uri.encodeComponent(widget.zone)}'
        '&last_id=0',
      );

      final response = await http.get(uri);

      if (response.statusCode != 200) {
        throw Exception('HTTP ${response.statusCode}');
      }

      final payload = jsonDecode(response.body) as Map<String, dynamic>;
      final data = (payload['data'] as List<dynamic>? ?? [])
          .whereType<Map<String, dynamic>>()
          .toList();

      if (data.isEmpty) {
        return;
      }

      for (final order in data) {
        final orderId = order['id'] as int? ?? 0;
        final orderDriverId = order['driver_id'] as int?;
        final orderZone = (order['zone'] as String? ?? '').toLowerCase();

        final sameDriver = orderDriverId == widget.driverId;
        final sameZone = orderZone == widget.zone.toLowerCase();

        if (!sameDriver || !sameZone || orderId == 0) {
          continue;
        }

        if (!_notifiedOrderIds.contains(orderId)) {
          await _showOrderNotification(order);
          _notifiedOrderIds.add(orderId);
        }

      }

      setState(() {
        for (final incoming in data) {
          final incomingId = incoming['id'] as int? ?? 0;
          if (incomingId == 0) {
            continue;
          }

          final existingIndex = _orders.indexWhere(
            (existing) => (existing['id'] as int? ?? 0) == incomingId,
          );

          if (existingIndex >= 0) {
            _orders[existingIndex] = incoming;
          } else {
            _orders.add(incoming);
          }
        }

        _orders.sort((a, b) {
          final idA = a['id'] as int? ?? 0;
          final idB = b['id'] as int? ?? 0;
          return idB.compareTo(idA);
        });
      });
    } catch (e) {
      if (!mounted) {
        return;
      }
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Gagal ambil data order: $e')),
      );
    } finally {
      if (mounted) {
        setState(() {
          _isLoading = false;
        });
      }
    }
  }

  Future<void> _showOrderNotification(Map<String, dynamic> order) async {
    const details = NotificationDetails(
      android: AndroidNotificationDetails(
        'driver_orders',
        'Driver Orders',
        channelDescription: 'Notifikasi order baru untuk supir',
        importance: Importance.max,
        priority: Priority.high,
      ),
    );

    final title = 'Order Baru #${order['id']}';
    final body =
        'Pelanggan: ${order['customer_name'] ?? '-'} | Zona: ${order['zone'] ?? '-'}';

    await localNotifications.show(
      order['id'] as int? ?? DateTime.now().millisecondsSinceEpoch,
      title,
      body,
      details,
    );
  }

  Future<void> _updateOrderStatus({
    required int orderId,
    required String status,
  }) async {
    if (_isUpdatingOrderIds.contains(orderId)) {
      return;
    }

    setState(() {
      _isUpdatingOrderIds.add(orderId);
    });

    try {
      final uri = Uri.parse('$apiBaseUrl/driver/orders/$orderId/status');
      final response = await http.patch(
        uri,
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({
          'driver_id': widget.driverId,
          'status': status,
        }),
      );

      final payload = jsonDecode(response.body) as Map<String, dynamic>;

      if (response.statusCode != 200) {
        final errorMessage =
            (payload['message'] as String?) ?? 'Gagal memproses order.';
        throw Exception(errorMessage);
      }

      if (!mounted) {
        return;
      }

      setState(() {
        final index = _orders.indexWhere(
          (order) => (order['id'] as int? ?? 0) == orderId,
        );
        if (index >= 0) {
          _orders[index] = {
            ..._orders[index],
            'status': status,
          };
        }
      });

      final successMessage =
          (payload['message'] as String?) ?? 'Order berhasil diproses.';
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(successMessage)),
      );
    } catch (e) {
      if (!mounted) {
        return;
      }
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Gagal update status: $e')),
      );
    } finally {
      if (mounted) {
        setState(() {
          _isUpdatingOrderIds.remove(orderId);
        });
      }
    }
  }

  Future<void> _confirmAndUpdateOrderStatus({
    required int orderId,
    required String status,
  }) async {
    final isApprove = status == 'approved';
    final actionText = isApprove ? 'terima' : 'tolak';

    final shouldProceed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) {
        return AlertDialog(
          title: Text(isApprove ? 'Terima Pesanan?' : 'Tolak Pesanan?'),
          content: Text('Anda yakin ingin $actionText pesanan #$orderId?'),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(dialogContext).pop(false),
              child: const Text('Batal'),
            ),
            FilledButton(
              onPressed: () => Navigator.of(dialogContext).pop(true),
              child: Text(isApprove ? 'Ya, Terima' : 'Ya, Tolak'),
            ),
          ],
        );
      },
    );

    if (shouldProceed == true) {
      await _updateOrderStatus(orderId: orderId, status: status);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('${widget.driverName} - ${widget.zone}'),
        actions: [
          Switch(
            value: _isOnline,
            onChanged: (value) {
              setState(() {
                _isOnline = value;
              });
            },
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: _fetchOrders,
        child: _orders.isEmpty
            ? ListView(
                children: const [
                  SizedBox(height: 120),
                  Center(child: Text('Belum ada order yang cocok.')),
                ],
              )
            : ListView.separated(
                itemCount: _orders.length,
                separatorBuilder: (_, _) => const Divider(height: 1),
                itemBuilder: (context, index) {
                  final order = _orders[index];
                  final orderId = order['id'] as int? ?? 0;
                    final status =
                      (order['status'] as String? ?? '-').trim().toLowerCase();
                    final isPending =
                      status != 'approved' && status != 'rejected';
                  final isUpdating = _isUpdatingOrderIds.contains(orderId);

                  return ListTile(
                    leading: CircleAvatar(child: Text('${order['id'] ?? '-'}')),
                    title: Text('Pelanggan: ${order['customer_name'] ?? '-'}'),
                    subtitle: Text(
                      'Zona: ${order['zone'] ?? '-'}\n'
                      'Items: ${order['items'] ?? '-'}',
                    ),
                    isThreeLine: true,
                    trailing: isPending
                        ? Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              OutlinedButton(
                                onPressed: isUpdating || orderId == 0
                                    ? null
                                    : () => _confirmAndUpdateOrderStatus(
                                          orderId: orderId,
                                          status: 'rejected',
                                        ),
                                style: OutlinedButton.styleFrom(
                                  foregroundColor: Colors.red,
                                  side: const BorderSide(color: Colors.red),
                                  minimumSize: const Size(0, 32),
                                  padding: const EdgeInsets.symmetric(
                                    horizontal: 12,
                                  ),
                                ),
                                child: const Text('Tolak'),
                              ),
                              const SizedBox(width: 8),
                              FilledButton(
                                onPressed: isUpdating || orderId == 0
                                    ? null
                                    : () => _confirmAndUpdateOrderStatus(
                                          orderId: orderId,
                                          status: 'approved',
                                        ),
                                style: FilledButton.styleFrom(
                                  minimumSize: const Size(0, 32),
                                  padding: const EdgeInsets.symmetric(
                                    horizontal: 12,
                                  ),
                                ),
                                child: Text(isUpdating ? '...' : 'Terima'),
                              ),
                            ],
                          )
                        : Text(status.toUpperCase()),
                  );
                },
              ),
      ),
    );
  }
}
