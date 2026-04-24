import 'dart:async';
import 'dart:convert';

import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:geolocator/geolocator.dart';
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
    const seed = Color(0xFF0F766E);
    final colorScheme = ColorScheme.fromSeed(
      seedColor: seed,
      brightness: Brightness.light,
    );

    return MaterialApp(
      debugShowCheckedModeBanner: false,
      title: 'Driver Notifier',
      theme: ThemeData(
        colorScheme: colorScheme,
        scaffoldBackgroundColor: const Color(0xFFF3F6FB),
        appBarTheme: AppBarTheme(
          elevation: 0,
          backgroundColor: const Color(0xFFF3F6FB),
          foregroundColor: const Color(0xFF0F172A),
          centerTitle: false,
          titleTextStyle: const TextStyle(
            color: Color(0xFF0F172A),
            fontSize: 18,
            fontWeight: FontWeight.w700,
            letterSpacing: 0.2,
          ),
        ),
        cardTheme: CardThemeData(
          elevation: 0,
          color: Colors.white,
          margin: EdgeInsets.zero,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(18),
            side: const BorderSide(color: Color(0xFFE2E8F0)),
          ),
        ),
        inputDecorationTheme: InputDecorationTheme(
          filled: true,
          fillColor: const Color(0xFFF8FAFC),
          contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
          border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(12),
            borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
          ),
          enabledBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(12),
            borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
          ),
          focusedBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(12),
            borderSide: BorderSide(color: colorScheme.primary, width: 1.4),
          ),
        ),
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
      body: Container(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
            colors: [Color(0xFFE6FFFA), Color(0xFFF3F6FB)],
          ),
        ),
        child: SafeArea(
          child: Center(
            child: SingleChildScrollView(
              padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 24),
              child: ConstrainedBox(
                constraints: const BoxConstraints(maxWidth: 420),
                child: Card(
                  child: Padding(
                    padding: const EdgeInsets.fromLTRB(20, 24, 20, 20),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Container(
                          width: 52,
                          height: 52,
                          decoration: BoxDecoration(
                            color: const Color(0xFF0F766E).withValues(alpha: 0.12),
                            borderRadius: BorderRadius.circular(14),
                          ),
                          child: const Icon(
                            Icons.local_shipping_rounded,
                            color: Color(0xFF0F766E),
                            size: 28,
                          ),
                        ),
                        const SizedBox(height: 14),
                        const Text(
                          'Masuk Aplikasi Supir',
                          style: TextStyle(fontSize: 22, fontWeight: FontWeight.w800),
                        ),
                        const SizedBox(height: 6),
                        const Text(
                          'Pantau order dan stok harian dengan cepat.',
                          style: TextStyle(color: Color(0xFF64748B)),
                        ),
                        const SizedBox(height: 18),
                        TextField(
                          controller: _usernameController,
                          decoration: const InputDecoration(
                            labelText: 'Username',
                            prefixIcon: Icon(Icons.person_outline),
                          ),
                        ),
                        const SizedBox(height: 12),
                        TextField(
                          controller: _passwordController,
                          obscureText: true,
                          decoration: const InputDecoration(
                            labelText: 'Password',
                            prefixIcon: Icon(Icons.lock_outline),
                          ),
                        ),
                        const SizedBox(height: 18),
                        SizedBox(
                          width: double.infinity,
                          child: FilledButton.icon(
                            onPressed: _isSubmitting ? null : _submitLogin,
                            icon: _isSubmitting
                                ? const SizedBox(
                                    width: 16,
                                    height: 16,
                                    child: CircularProgressIndicator(strokeWidth: 2),
                                  )
                                : const Icon(Icons.login_rounded),
                            label: Text(_isSubmitting ? 'Memproses...' : 'Login Supir'),
                            style: FilledButton.styleFrom(
                              padding: const EdgeInsets.symmetric(vertical: 13),
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(12),
                              ),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            ),
          ),
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
  final Set<int> _notifiedOrderIds = <int>{};
  Timer? _timer;
  bool _isOnline = true;
  bool _isLoading = false;
  bool _isLoadingTodayStock = false;
  bool _isSubmittingStock = false;
  bool _isLoggingOut = false;
  bool _isSessionExpiredHandled = false;
  final Set<int> _isUpdatingOrderIds = <int>{};
  int _todayStock5Kg = 0;
  int _todayStock20Kg = 0;
  bool _hasTodayStockInput = false;

  final _stock5KgController = TextEditingController();
  final _stock20KgController = TextEditingController();

  @override
  void initState() {
    super.initState();
    _startPolling();
    _fetchTodayStock();
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

  Future<void> _fetchTodayStock() async {
    if (_isLoadingTodayStock) {
      return;
    }

    setState(() {
      _isLoadingTodayStock = true;
    });

    try {
      final uri = Uri.parse('$apiBaseUrl/driver/stocks/today');
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
        final message =
            (payload['message'] as String?) ?? 'Gagal memuat stok hari ini.';
        throw Exception(message);
      }

      final data = payload['data'] as Map<String, dynamic>? ?? {};

      if (!mounted) {
        return;
      }

      setState(() {
        _todayStock5Kg = (data['stock_5kg'] as num?)?.toInt() ?? 0;
        _todayStock20Kg = (data['stock_20kg'] as num?)?.toInt() ?? 0;
        _hasTodayStockInput = data['has_stock_input'] == true;
      });
    } catch (e) {
      if (!mounted) {
        return;
      }

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Gagal ambil stok hari ini: $e')),
      );
    } finally {
      if (mounted) {
        setState(() {
          _isLoadingTodayStock = false;
        });
      }
    }
  }

  Future<void> _submitDriverStock() async {
    if (_isSubmittingStock) {
      return;
    }

    if (_hasTodayStockInput) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Stok hari ini sudah diinput. Input ulang tidak diperbolehkan.'),
        ),
      );
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
      setState(() {
        _hasTodayStockInput = true;
      });
      _fetchTodayStock();
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
    _fetchTodayStock();
    _timer = Timer.periodic(const Duration(seconds: 10), (_) {
      if (_isOnline) {
        _fetchOrders();
        _fetchTodayStock();
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

      final responseData = payload['data'] as Map<String, dynamic>?;
      final updatedStatus =
          (responseData?['status'] as String? ?? status).trim().toLowerCase();
      final updatedDriverId = (responseData?['driver_id'] as num?)?.toInt();
        final stockToday = responseData?['stock_today'] as Map<String, dynamic>?;

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
            'status': updatedStatus,
            'driver_id': updatedDriverId,
          };
        }

        if (stockToday != null) {
          _todayStock5Kg = (stockToday['stock_5kg'] as num?)?.toInt() ?? _todayStock5Kg;
          _todayStock20Kg =
              (stockToday['stock_20kg'] as num?)?.toInt() ?? _todayStock20Kg;
          _hasTodayStockInput = true;
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

  Future<Position> _resolveDriverPosition() async {
    final serviceEnabled = await Geolocator.isLocationServiceEnabled();
    if (!serviceEnabled) {
      throw Exception('GPS belum aktif. Silakan aktifkan layanan lokasi.');
    }

    var permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.denied) {
      permission = await Geolocator.requestPermission();
    }

    if (permission == LocationPermission.denied) {
      throw Exception('Izin lokasi ditolak. Mohon izinkan lokasi untuk lanjut.');
    }

    if (permission == LocationPermission.deniedForever) {
      throw Exception(
        'Izin lokasi ditolak permanen. Aktifkan kembali dari pengaturan aplikasi.',
      );
    }

    return Geolocator.getCurrentPosition(
      desiredAccuracy: LocationAccuracy.high,
      timeLimit: const Duration(seconds: 15),
    );
  }

  Future<void> _completeDelivery(int orderId) async {
    if (_isUpdatingOrderIds.contains(orderId)) {
      return;
    }

    final shouldProceed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) {
        return AlertDialog(
          title: const Text('Selesaikan Antar?'),
          content: Text(
            'Sistem akan cek GPS Anda. Order #$orderId hanya bisa selesai jika jarak < 500 meter.',
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(dialogContext).pop(false),
              child: const Text('Batal'),
            ),
            FilledButton(
              onPressed: () => Navigator.of(dialogContext).pop(true),
              child: const Text('Lanjut'),
            ),
          ],
        );
      },
    );

    if (shouldProceed != true) {
      return;
    }

    setState(() {
      _isUpdatingOrderIds.add(orderId);
    });

    try {
      final position = await _resolveDriverPosition();
      final uri = Uri.parse('$apiBaseUrl/driver/orders/$orderId/complete');
      final response = await http.patch(
        uri,
        headers: _authHeaders(json: true),
        body: jsonEncode({
          'latitude': position.latitude,
          'longitude': position.longitude,
        }),
      );

      final payload = jsonDecode(response.body) as Map<String, dynamic>;

      if (response.statusCode == 401) {
        _handleUnauthorized();
        return;
      }

      if (response.statusCode != 200) {
        final message = (payload['message'] as String?) ?? 'Gagal menyelesaikan antar.';
        final distance = (payload['distance_m'] as num?)?.toInt();
        final maxDistance = (payload['max_distance_m'] as num?)?.toInt();
        final fullMessage = (distance != null && maxDistance != null)
            ? '$message (Jarak Anda ${distance}m, maksimal ${maxDistance}m).'
            : message;
        throw Exception(fullMessage);
      }

      final responseData = payload['data'] as Map<String, dynamic>?;
      final updatedStatus =
          (responseData?['status'] as String? ?? 'completed').trim().toLowerCase();
      final updatedDriverId = (responseData?['driver_id'] as num?)?.toInt();
      final distance = (responseData?['distance_m'] as num?)?.toInt();
      final stockToday = responseData?['stock_today'] as Map<String, dynamic>?;

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
            'status': updatedStatus,
            'driver_id': updatedDriverId,
          };
        }

        if (stockToday != null) {
          _todayStock5Kg = (stockToday['stock_5kg'] as num?)?.toInt() ?? _todayStock5Kg;
          _todayStock20Kg =
              (stockToday['stock_20kg'] as num?)?.toInt() ?? _todayStock20Kg;
          _hasTodayStockInput = true;
        }
      });

      final successMessage = (payload['message'] as String?) ?? 'Pesanan selesai diantar.';
      final finalMessage = distance != null
          ? '$successMessage (Jarak tervalidasi: ${distance}m)'
          : successMessage;

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(finalMessage)),
      );
    } catch (e) {
      if (!mounted) {
        return;
      }

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Gagal selesai antar: $e')),
      );
    } finally {
      if (mounted) {
        setState(() {
          _isUpdatingOrderIds.remove(orderId);
        });
      }
    }
  }

  Future<void> _refreshAll() async {
    await Future.wait([
      _fetchOrders(),
      _fetchTodayStock(),
    ]);
  }

  Color _statusBgColor(String status, bool isClaimedByOtherDriver) {
    if (isClaimedByOtherDriver) {
      return const Color(0xFFE2E8F0);
    }

    switch (status) {
      case 'approved':
        return const Color(0xFFD1FAE5);
      case 'completed':
        return const Color(0xFFDBEAFE);
      case 'rejected':
        return const Color(0xFFFEE2E2);
      default:
        return const Color(0xFFFFF1CC);
    }
  }

  Color _statusTextColor(String status, bool isClaimedByOtherDriver) {
    if (isClaimedByOtherDriver) {
      return const Color(0xFF334155);
    }

    switch (status) {
      case 'approved':
        return const Color(0xFF047857);
      case 'completed':
        return const Color(0xFF1D4ED8);
      case 'rejected':
        return const Color(0xFFB91C1C);
      default:
        return const Color(0xFF92400E);
    }
  }

  String _statusLabel(String status, bool isClaimedByOtherDriver) {
    if (isClaimedByOtherDriver) {
      return 'Diproses Supir Lain';
    }

    switch (status) {
      case 'approved':
        return 'Diterima';
      case 'completed':
        return 'Selesai Antar';
      case 'rejected':
        return 'Ditolak';
      default:
        return 'Pending';
    }
  }

  Widget _buildTodayStockTile({
    required String label,
    required int value,
    required Color accent,
  }) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: accent.withValues(alpha: 0.09),
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: accent.withValues(alpha: 0.35)),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(label, style: const TextStyle(color: Color(0xFF475569), fontSize: 12)),
            const SizedBox(height: 4),
            Text(
              '$value pcs',
              style: TextStyle(
                color: accent,
                fontSize: 18,
                fontWeight: FontWeight.w800,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildOrderCard(Map<String, dynamic> order) {
    final orderId = order['id'] as int? ?? 0;
    final status = (order['status'] as String? ?? '-').trim().toLowerCase();
    final isPending = status == 'pending';
    final isCompleted = status == 'completed';
    final orderDriverId = (order['driver_id'] as num?)?.toInt();
    final isClaimedByOtherDriver =
        isPending && orderDriverId != null && orderDriverId != widget.driverId;
    final isApprovedByCurrentDriver =
      status == 'approved' && orderDriverId == widget.driverId;
    final isApprovedByOtherDriver =
      status == 'approved' && orderDriverId != null && orderDriverId != widget.driverId;
    final isUpdating = _isUpdatingOrderIds.contains(orderId);

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                CircleAvatar(
                  radius: 18,
                  backgroundColor: const Color(0xFFE2E8F0),
                  child: Text(
                    '#$orderId',
                    style: const TextStyle(
                      color: Color(0xFF0F172A),
                      fontSize: 11,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        order['customer_name']?.toString().trim().isNotEmpty == true
                            ? order['customer_name'].toString()
                            : 'Pelanggan',
                        style: const TextStyle(
                          fontWeight: FontWeight.w700,
                          fontSize: 15,
                        ),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        'Zona ${order['zone'] ?? '-'}',
                        style: const TextStyle(color: Color(0xFF64748B), fontSize: 12),
                      ),
                    ],
                  ),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                  decoration: BoxDecoration(
                    color: _statusBgColor(status, isClaimedByOtherDriver),
                    borderRadius: BorderRadius.circular(999),
                  ),
                  child: Text(
                    _statusLabel(status, isClaimedByOtherDriver),
                    style: TextStyle(
                      color: _statusTextColor(status, isClaimedByOtherDriver),
                      fontWeight: FontWeight.w700,
                      fontSize: 11,
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                color: const Color(0xFFF8FAFC),
                borderRadius: BorderRadius.circular(10),
                border: Border.all(color: const Color(0xFFE2E8F0)),
              ),
              child: Text(
                _formatOrderItems(order['items']),
                style: const TextStyle(fontWeight: FontWeight.w600),
              ),
            ),
            const SizedBox(height: 12),
            if (isPending && !isClaimedByOtherDriver)
              Row(
                children: [
                  Expanded(
                    child: OutlinedButton(
                      onPressed: isUpdating || orderId == 0
                          ? null
                          : () => _confirmAndUpdateOrderStatus(
                                orderId: orderId,
                                status: 'rejected',
                              ),
                      style: OutlinedButton.styleFrom(
                        foregroundColor: const Color(0xFFB91C1C),
                        side: const BorderSide(color: Color(0xFFEF4444)),
                        padding: const EdgeInsets.symmetric(vertical: 12),
                      ),
                      child: const Text('Tolak'),
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: FilledButton(
                      onPressed: isUpdating || orderId == 0
                          ? null
                          : () => _confirmAndUpdateOrderStatus(
                                orderId: orderId,
                                status: 'approved',
                              ),
                      style: FilledButton.styleFrom(
                        padding: const EdgeInsets.symmetric(vertical: 12),
                      ),
                      child: Text(isUpdating ? 'Memproses...' : 'Terima'),
                    ),
                  ),
                ],
              )
            else if (isApprovedByCurrentDriver)
              SizedBox(
                width: double.infinity,
                child: FilledButton.icon(
                  onPressed: isUpdating || orderId == 0
                      ? null
                      : () => _completeDelivery(orderId),
                  style: FilledButton.styleFrom(
                    backgroundColor: const Color(0xFF2563EB),
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(vertical: 12),
                  ),
                  icon: const Icon(Icons.check_circle_outline),
                  label: Text(isUpdating ? 'Memproses...' : 'Selesai Antar'),
                ),
              )
            else
              Row(
                children: [
                  const Icon(Icons.info_outline, size: 16, color: Color(0xFF64748B)),
                  const SizedBox(width: 6),
                  Text(
                    isClaimedByOtherDriver
                        ? 'Order sedang diproses oleh supir lain'
                        : isApprovedByOtherDriver
                        ? 'Order sudah diterima supir lain'
                        : isCompleted
                        ? 'Order sudah selesai diantar'
                        : 'Order sudah diproses',
                    style: const TextStyle(color: Color(0xFF64748B)),
                  ),
                ],
              ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        automaticallyImplyLeading: false,
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(widget.driverName),
            const SizedBox(height: 2),
            Text(
              widget.zone,
              style: const TextStyle(fontSize: 12, color: Color(0xFF64748B)),
            ),
          ],
        ),
        actions: [
          Container(
            margin: const EdgeInsets.only(right: 6),
            padding: const EdgeInsets.symmetric(horizontal: 6),
            decoration: BoxDecoration(
              color: _isOnline
                  ? const Color(0xFFDCFCE7)
                  : const Color(0xFFE2E8F0),
              borderRadius: BorderRadius.circular(999),
            ),
            child: Row(
              children: [
                Text(
                  _isOnline ? 'Online' : 'Offline',
                  style: TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.w600,
                    color: _isOnline ? const Color(0xFF166534) : const Color(0xFF475569),
                  ),
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
          ),
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
        ],
      ),
      body: RefreshIndicator(
        onRefresh: _refreshAll,
        child: ListView(
          padding: const EdgeInsets.fromLTRB(12, 6, 12, 18),
          children: [
            Card(
              child: Padding(
                padding: const EdgeInsets.all(14),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        const Icon(Icons.inventory_2_outlined, size: 18),
                        const SizedBox(width: 8),
                        const Text(
                          'Stok Bawaan Hari Ini',
                          style: TextStyle(fontWeight: FontWeight.w700),
                        ),
                        const Spacer(),
                        Chip(
                          visualDensity: VisualDensity.compact,
                          label: Text(_formatDateYmd(DateTime.now())),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),
                    Row(
                      children: [
                        _buildTodayStockTile(
                          label: 'Sisa 5kg',
                          value: _todayStock5Kg,
                          accent: const Color(0xFF0F766E),
                        ),
                        const SizedBox(width: 10),
                        _buildTodayStockTile(
                          label: 'Sisa 20kg',
                          value: _todayStock20Kg,
                          accent: const Color(0xFF1D4ED8),
                        ),
                      ],
                    ),
                    const SizedBox(height: 10),
                    if (_isLoadingTodayStock)
                      const LinearProgressIndicator(minHeight: 2),
                    if (!_isLoadingTodayStock)
                      Text(
                        _hasTodayStockInput
                            ? 'Stok tersinkron otomatis setiap 10 detik.'
                            : 'Belum ada input stok untuk hari ini.',
                        style: const TextStyle(color: Color(0xFF64748B), fontSize: 12),
                      ),
                    const SizedBox(height: 12),
                    Row(
                      children: [
                        Expanded(
                          child: TextField(
                            controller: _stock5KgController,
                            enabled: !_hasTodayStockInput,
                            keyboardType: TextInputType.number,
                            decoration: const InputDecoration(
                              labelText: 'Input 5kg',
                              prefixIcon: Icon(Icons.icecream_outlined),
                            ),
                          ),
                        ),
                        const SizedBox(width: 10),
                        Expanded(
                          child: TextField(
                            controller: _stock20KgController,
                            enabled: !_hasTodayStockInput,
                            keyboardType: TextInputType.number,
                            decoration: const InputDecoration(
                              labelText: 'Input 20kg',
                              prefixIcon: Icon(Icons.ac_unit_outlined),
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 10),
                    SizedBox(
                      width: double.infinity,
                      child: FilledButton.icon(
                        onPressed: (_isSubmittingStock || _hasTodayStockInput)
                            ? null
                            : _submitDriverStock,
                        icon: _isSubmittingStock
                            ? const SizedBox(
                                width: 16,
                                height: 16,
                                child: CircularProgressIndicator(strokeWidth: 2),
                              )
                            : const Icon(Icons.save_outlined),
                        label: Text(
                          _isSubmittingStock
                              ? 'Menyimpan...'
                              : (_hasTodayStockInput
                                  ? 'Stok Hari Ini Sudah Diinput'
                                  : 'Simpan Stok'),
                        ),
                        style: FilledButton.styleFrom(
                          padding: const EdgeInsets.symmetric(vertical: 12),
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 12),
            Row(
              children: [
                const Text(
                  'Daftar Order',
                  style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800),
                ),
                const SizedBox(width: 8),
                if (_isLoading)
                  const SizedBox(
                    width: 16,
                    height: 16,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  ),
              ],
            ),
            const SizedBox(height: 8),
            if (_orders.isEmpty)
              const Card(
                child: Padding(
                  padding: EdgeInsets.all(18),
                  child: Center(child: Text('Belum ada order yang cocok.')),
                ),
              )
            else
              ..._orders.map(_buildOrderCard),
          ],
        ),
      ),
    );
  }
}
