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
      final token = (data['token'] as String? ?? '').trim();
      final safeDriverName = driverName.isEmpty ? 'Supir #$driverId' : driverName;

      if (driverId == null || zone.isEmpty || token.isEmpty) {
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
            authToken: token,
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
    required this.authToken,
  });

  final int driverId;
  final String driverName;
  final String zone;
  final String authToken;

  @override
  State<DriverHomeScreen> createState() => _DriverHomeScreenState();
}

class _DriverHomeScreenState extends State<DriverHomeScreen> {
  final List<Map<String, dynamic>> _orders = [];
  final List<Map<String, dynamic>> _stockHistory = [];
  final Set<int> _notifiedOrderIds = <int>{};
  Timer? _timer;
  bool _isOnline = true;
  bool _isLoading = false;
  bool _isLoadingStockHistory = false;
  bool _isSubmittingStock = false;
  bool _isLoggingOut = false;
  bool _isSessionExpiredHandled = false;
  final Set<int> _isUpdatingOrderIds = <int>{};

  final _stock5KgController = TextEditingController();
  final _stock20KgController = TextEditingController();

  @override
  void initState() {
    super.initState();
    _startPolling();
    _fetchStockHistory();
  }

  @override
  void dispose() {
    _timer?.cancel();
    _stock5KgController.dispose();
    _stock20KgController.dispose();
    super.dispose();
  }

  Map<String, String> _authHeaders({bool json = false}) {
    final headers = <String, String>{
      'Authorization': 'Bearer ${widget.authToken}',
    };

    if (json) {
      headers['Content-Type'] = 'application/json';
    }

    return headers;
  }

  void _handleUnauthorized() {
    if (_isSessionExpiredHandled || !mounted) {
      return;
    }

    _isSessionExpiredHandled = true;

    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Sesi login supir habis. Silakan login ulang.')),
    );

    Navigator.pushAndRemoveUntil(
      context,
      MaterialPageRoute(builder: (_) => const LoginScreen()),
      (route) => false,
    );
  }

  Future<void> _logout() async {
    if (_isLoggingOut) {
      return;
    }

    final confirmed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) {
        return AlertDialog(
          title: const Text('Logout?'),
          content: const Text('Anda yakin ingin keluar dari aplikasi supir?'),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(dialogContext).pop(false),
              child: const Text('Batal'),
            ),
            FilledButton(
              onPressed: () => Navigator.of(dialogContext).pop(true),
              child: const Text('Logout'),
            ),
          ],
        );
      },
    );

    if (confirmed != true || !mounted) {
      return;
    }

    setState(() {
      _isLoggingOut = true;
    });

    try {
      final uri = Uri.parse('$apiBaseUrl/driver/logout');
      await http.post(uri, headers: _authHeaders());
    } catch (_) {
      // Ignore API logout failure and continue local logout for better UX.
    } finally {
      if (mounted) {
        Navigator.pushAndRemoveUntil(
          context,
          MaterialPageRoute(builder: (_) => const LoginScreen()),
          (route) => false,
        );
      }
    }
  }

  String _formatDateYmd(DateTime date) {
    final year = date.year.toString().padLeft(4, '0');
    final month = date.month.toString().padLeft(2, '0');
    final day = date.day.toString().padLeft(2, '0');
    return '$year-$month-$day';
  }

  String _formatHistoryDate(String rawDate) {
    try {
      final parsed = DateTime.parse(rawDate).toLocal();
      return _formatDateYmd(parsed);
    } catch (_) {
      if (rawDate.length >= 10) {
        return rawDate.substring(0, 10);
      }
      return rawDate;
    }
  }

  int? _extractQtyByWeight(String text, int weight) {
    final pattern = RegExp(
      '(?:\\b$weight\\s*kg\\b\\D{0,18}(\\d+)|(\\d+)\\D{0,18}\\b$weight\\s*kg\\b)',
      caseSensitive: false,
    );

    final match = pattern.firstMatch(text);
    if (match == null) {
      return null;
    }

    final front = match.group(1);
    final back = match.group(2);
    return int.tryParse(front ?? back ?? '');
  }

  String _formatOrderItems(dynamic rawItems) {
    final source = (rawItems ?? '').toString().trim();
    if (source.isEmpty) {
      return '-';
    }

    final qty5 = _extractQtyByWeight(source, 5);
    final qty20 = _extractQtyByWeight(source, 20);

    if (qty5 != null || qty20 != null) {
      final parts = <String>[];
      if (qty5 != null) {
        parts.add('5kg - $qty5''pcs');
      }
      if (qty20 != null) {
        parts.add('20kg - $qty20''pcs');
      }
      return parts.join(' | ');
    }

    return source
        .replaceAll(RegExp('\\bpcs?\\b', caseSensitive: false), '')
        .replaceAll(RegExp('\\s+'), ' ')
        .trim();
  }

  Future<void> _fetchStockHistory() async {
    if (_isLoadingStockHistory) {
      return;
    }

    setState(() {
      _isLoadingStockHistory = true;
    });

    try {
      final uri = Uri.parse('$apiBaseUrl/driver/stocks/history');
      final response = await http.get(
        uri,
        headers: _authHeaders(),
      );

      final payload = jsonDecode(response.body) as Map<String, dynamic>;
      if (response.statusCode == 401) {
        _handleUnauthorized();
        return;
      }

      if (response.statusCode != 200) {
        final message = (payload['message'] as String?) ?? 'Gagal memuat riwayat stok.';
        throw Exception(message);
      }

      final data = (payload['data'] as List<dynamic>? ?? [])
          .whereType<Map<String, dynamic>>()
          .toList();

      if (!mounted) {
        return;
      }

      setState(() {
        _stockHistory
          ..clear()
          ..addAll(data);
      });
    } catch (e) {
      if (!mounted) {
        return;
      }

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Gagal ambil riwayat stok: $e')),
      );
    } finally {
      if (mounted) {
        setState(() {
          _isLoadingStockHistory = false;
        });
      }
    }
  }

  Future<void> _submitDriverStock() async {
    if (_isSubmittingStock) {
      return;
    }

    final stock5kg = int.tryParse(_stock5KgController.text.trim());
    final stock20kg = int.tryParse(_stock20KgController.text.trim());

    if (stock5kg == null || stock5kg < 0 || stock20kg == null || stock20kg < 0) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Input stok harus angka 0 atau lebih.')),
      );
      return;
    }

    setState(() {
      _isSubmittingStock = true;
    });

    try {
      final today = DateTime.now();
      final uri = Uri.parse('$apiBaseUrl/driver/stocks');
      final response = await http.post(
        uri,
        headers: _authHeaders(json: true),
        body: jsonEncode({
          'date': _formatDateYmd(today),
          'stock_5kg': stock5kg,
          'stock_20kg': stock20kg,
        }),
      );

      final payload = jsonDecode(response.body) as Map<String, dynamic>;

      if (response.statusCode == 401) {
        _handleUnauthorized();
        return;
      }

      if (response.statusCode != 200) {
        final message =
            (payload['message'] as String?) ?? 'Gagal menyimpan stok bawaan.';
        throw Exception(message);
      }

      if (!mounted) {
        return;
      }

      final message =
          (payload['message'] as String?) ?? 'Stok bawaan berhasil disimpan.';
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(message)));
      _fetchStockHistory();
    } catch (e) {
      if (!mounted) {
        return;
      }

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Gagal simpan stok: $e')),
      );
    } finally {
      if (mounted) {
        setState(() {
          _isSubmittingStock = false;
        });
      }
    }
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
      final uri = Uri.parse('$apiBaseUrl/driver/orders/notifications');

      final response = await http.get(
        uri,
        headers: _authHeaders(),
      );

      if (response.statusCode == 401) {
        _handleUnauthorized();
        return;
      }

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
        final orderDriverId = (order['driver_id'] as num?)?.toInt();
        final orderZone = (order['zone'] as String? ?? '').toLowerCase();
        final orderStatus = (order['status'] as String? ?? '').toLowerCase();

        final sameZone = orderZone == widget.zone.toLowerCase();
        final canProcessOrder =
            orderStatus == 'pending' &&
            (orderDriverId == null || orderDriverId == widget.driverId);

        if (!sameZone || orderId == 0) {
          continue;
        }

        if (canProcessOrder && !_notifiedOrderIds.contains(orderId)) {
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
        headers: _authHeaders(json: true),
        body: jsonEncode({
          'status': status,
        }),
      );

      final payload = jsonDecode(response.body) as Map<String, dynamic>;

      if (response.statusCode == 401) {
        _handleUnauthorized();
        return;
      }

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

  Future<void> _refreshAll() async {
    await Future.wait([
      _fetchOrders(),
      _fetchStockHistory(),
    ]);
  }

  Widget _buildStockHistorySection() {
    if (_isLoadingStockHistory) {
      return const Padding(
        padding: EdgeInsets.symmetric(vertical: 8),
        child: LinearProgressIndicator(minHeight: 2),
      );
    }

    if (_stockHistory.isEmpty) {
      return const Padding(
        padding: EdgeInsets.symmetric(vertical: 8),
        child: Text('Belum ada riwayat stok yang diinput.'),
      );
    }

    return Column(
      children: _stockHistory.take(5).map((row) {
        final date = _formatHistoryDate((row['date'] as String? ?? '-').trim());
        final stock5 = row['stock_5kg']?.toString() ?? '0';
        final stock20 = row['stock_20kg']?.toString() ?? '0';

        return ListTile(
          dense: true,
          contentPadding: EdgeInsets.zero,
          title: Text(date),
          subtitle: Text('5kg: $stock5 pcs | 20kg: $stock20 pcs'),
          leading: const Icon(Icons.history, size: 18),
        );
      }).toList(),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        automaticallyImplyLeading: false,
        title: Text('${widget.driverName} - ${widget.zone}'),
        actions: [
          IconButton(
            tooltip: 'Logout',
            onPressed: _isLoggingOut ? null : _logout,
            icon: _isLoggingOut
                ? const SizedBox(
                    width: 18,
                    height: 18,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  )
                : const Icon(Icons.logout),
          ),
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
      body: Column(
        children: [
          Container(
            width: double.infinity,
            margin: const EdgeInsets.fromLTRB(12, 12, 12, 0),
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: const Color(0xFFE2E8F0)),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'Input Stok Bawaan Hari Ini',
                  style: TextStyle(fontWeight: FontWeight.w600),
                ),
                const SizedBox(height: 10),
                Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: [
                    SizedBox(
                      width: 150,
                      child: TextField(
                        controller: _stock5KgController,
                        keyboardType: TextInputType.number,
                        decoration: const InputDecoration(
                          labelText: 'Stok 5kg',
                          border: OutlineInputBorder(),
                          isDense: true,
                        ),
                      ),
                    ),
                    SizedBox(
                      width: 150,
                      child: TextField(
                        controller: _stock20KgController,
                        keyboardType: TextInputType.number,
                        decoration: const InputDecoration(
                          labelText: 'Stok 20kg',
                          border: OutlineInputBorder(),
                          isDense: true,
                        ),
                      ),
                    ),
                    OutlinedButton.icon(
                      onPressed: null,
                      icon: const Icon(Icons.calendar_today_outlined),
                      label: Text('Hari ini: ${_formatDateYmd(DateTime.now())}'),
                    ),
                    FilledButton(
                      onPressed: _isSubmittingStock ? null : _submitDriverStock,
                      child: Text(_isSubmittingStock ? 'Menyimpan...' : 'Simpan Stok'),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                const Text(
                  'Riwayat Input Stok Anda',
                  style: TextStyle(fontWeight: FontWeight.w600),
                ),
                _buildStockHistorySection(),
              ],
            ),
          ),
          Expanded(
            child: RefreshIndicator(
              onRefresh: _refreshAll,
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
                  final orderDriverId = (order['driver_id'] as num?)?.toInt();
                  final isClaimedByOtherDriver =
                      isPending && orderDriverId != null && orderDriverId != widget.driverId;
                  final isUpdating = _isUpdatingOrderIds.contains(orderId);

                      return ListTile(
                        leading: CircleAvatar(child: Text('${order['id'] ?? '-'}')),
                        title: Text('Pelanggan: ${order['customer_name'] ?? '-'}'),
                        subtitle: Text(
                          'Zona: ${order['zone'] ?? '-'}\n'
                          'Items: ${_formatOrderItems(order['items'])}',
                        ),
                        isThreeLine: true,
                        trailing: isPending && !isClaimedByOtherDriver
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
                            : Text(
                                isClaimedByOtherDriver
                                    ? 'DIPROSES SUPIR LAIN'
                                    : status.toUpperCase(),
                              ),
                      );
                    },
                  ),
            ),
          ),
        ],
      ),
    );
  }
}
