import 'dart:async';
import 'dart:convert';

import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
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

// Channel Android — harus konsisten di semua tempat
const AndroidNotificationChannel _kDriverOrderChannel = AndroidNotificationChannel(
  'driver_orders',
  'Order Masuk',
  description: 'Notifikasi order baru untuk supir Pools Ice',
  importance: Importance.max,
  playSound: true,
);

/// Handler untuk pesan FCM saat app BACKGROUND atau TERMINATED.
/// Harus top-level function (bukan method class) dan diberi @pragma.
@pragma('vm:entry-point')
Future<void> _firebaseMessagingBackgroundHandler(RemoteMessage message) async {
  await Firebase.initializeApp();

  // Init local notifications untuk background
  const androidSettings = AndroidInitializationSettings('@mipmap/ic_launcher');
  final plugin = FlutterLocalNotificationsPlugin();
  await plugin.initialize(const InitializationSettings(android: androidSettings));

  // Buat channel (idempotent — aman dipanggil berulang)
  await plugin
      .resolvePlatformSpecificImplementation<AndroidFlutterLocalNotificationsPlugin>()
      ?.createNotificationChannel(_kDriverOrderChannel);

  final notification = message.notification;
  final data = message.data;

  final title = notification?.title ?? data['title'] ?? '🛵 Order Baru';
  final body  = notification?.body  ?? data['body']  ?? 'Ada pesanan baru masuk!';

  await plugin.show(
    message.hashCode,
    title,
    body,
    NotificationDetails(
      android: AndroidNotificationDetails(
        _kDriverOrderChannel.id,
        _kDriverOrderChannel.name,
        channelDescription: _kDriverOrderChannel.description,
        importance: Importance.max,
        priority: Priority.high,
        playSound: true,
        icon: '@mipmap/ic_launcher',
      ),
    ),
  );
}

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await Firebase.initializeApp();
  FirebaseMessaging.onBackgroundMessage(_firebaseMessagingBackgroundHandler);
  await _initNotifications();
  runApp(const DriverApp());
}

Future<void> _initNotifications() async {
  const androidSettings = AndroidInitializationSettings('@mipmap/ic_launcher');
  const initSettings = InitializationSettings(android: androidSettings);

  await localNotifications.initialize(
    initSettings,
    // Handle tap notifikasi saat app background (bukan terminated)
    onDidReceiveNotificationResponse: (NotificationResponse response) {
      // Bisa digunakan untuk navigasi ke halaman order tertentu
    },
  );

  // Buat channel Android (penting untuk Android 8+)
  await localNotifications
      .resolvePlatformSpecificImplementation<AndroidFlutterLocalNotificationsPlugin>()
      ?.createNotificationChannel(_kDriverOrderChannel);

  await localNotifications
      .resolvePlatformSpecificImplementation<AndroidFlutterLocalNotificationsPlugin>()
      ?.requestNotificationsPermission();

  // Izinkan FCM menampilkan notifikasi di foreground (penting untuk iOS, opsional Android)
  await FirebaseMessaging.instance.setForegroundNotificationPresentationOptions(
    alert: true,
    badge: true,
    sound: true,
  );
}

class DriverApp extends StatelessWidget {
  const DriverApp({super.key});

  @override
  Widget build(BuildContext context) {
    const seed = Color(0xFF2563EB); // Royal Blue
    final colorScheme = ColorScheme.fromSeed(
      seedColor: seed,
      brightness: Brightness.light,
      surface: const Color(0xFFF8FAFC),
    );

    return MaterialApp(
      debugShowCheckedModeBanner: false,
      title: 'Pools Ice',
      theme: ThemeData(
        colorScheme: colorScheme,
        scaffoldBackgroundColor: const Color(0xFFF8FAFC),
        appBarTheme: const AppBarTheme(
          elevation: 0,
          scrolledUnderElevation: 2,
          shadowColor: Color(0x33000000),
          backgroundColor: Colors.white,
          foregroundColor: Color(0xFF0F172A),
          centerTitle: false,
          titleTextStyle: TextStyle(
            color: Color(0xFF0F172A),
            fontSize: 20,
            fontWeight: FontWeight.w700,
            letterSpacing: -0.5,
          ),
        ),
        cardTheme: CardThemeData(
          elevation: 0,
          color: Colors.white,
          margin: EdgeInsets.zero,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(20),
          ),
        ),
        inputDecorationTheme: InputDecorationTheme(
          filled: true,
          fillColor: const Color(0xFFF1F5F9),
          contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
          border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(16),
            borderSide: BorderSide.none,
          ),
          enabledBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(16),
            borderSide: BorderSide.none,
          ),
          focusedBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(16),
            borderSide: BorderSide(color: colorScheme.primary, width: 1.5),
          ),
          errorBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(16),
            borderSide: const BorderSide(color: Color(0xFFEF4444), width: 1.5),
          ),
          labelStyle: const TextStyle(color: Color(0xFF64748B)),
          hintStyle: const TextStyle(color: Color(0xFF94A3B8)),
        ),
        filledButtonTheme: FilledButtonThemeData(
          style: FilledButton.styleFrom(
            elevation: 0,
            padding: const EdgeInsets.symmetric(vertical: 16, horizontal: 24),
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(16),
            ),
            textStyle: const TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.w600,
              letterSpacing: 0.2,
            ),
          ),
        ),
        useMaterial3: true,
      ),
      home: const SplashScreen(),
    );
  }
}

class SplashScreen extends StatefulWidget {
  const SplashScreen({super.key});

  @override
  State<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends State<SplashScreen> with SingleTickerProviderStateMixin {
  late AnimationController _animationController;
  late Animation<double> _fadeAnimation;
  late Animation<double> _scaleAnimation;

  @override
  void initState() {
    super.initState();
    _animationController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1500),
    );

    _fadeAnimation = Tween<double>(begin: 0.0, end: 1.0).animate(
      CurvedAnimation(
        parent: _animationController,
        curve: const Interval(0.0, 0.6, curve: Curves.easeIn),
      ),
    );

    _scaleAnimation = Tween<double>(begin: 0.8, end: 1.0).animate(
      CurvedAnimation(
        parent: _animationController,
        curve: const Interval(0.0, 0.6, curve: Curves.easeOutBack),
      ),
    );

    _animationController.forward();

    Timer(const Duration(milliseconds: 2500), () {
      if (mounted) {
        Navigator.of(context).pushReplacement(
          PageRouteBuilder(
            transitionDuration: const Duration(milliseconds: 600),
            pageBuilder: (_, __, ___) => const LoginScreen(),
            transitionsBuilder: (_, animation, __, child) {
              return FadeTransition(opacity: animation, child: child);
            },
          ),
        );
      }
    });
  }

  @override
  void dispose() {
    _animationController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      body: Center(
        child: AnimatedBuilder(
          animation: _animationController,
          builder: (context, child) {
            return FadeTransition(
              opacity: _fadeAnimation,
              child: Transform.scale(
                scale: _scaleAnimation.value,
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Image.asset(
                      'assets/images/poolsice.png',
                      width: 140,
                      height: 140,
                      fit: BoxFit.contain,
                      errorBuilder: (context, error, stackTrace) => const Icon(
                        Icons.image_not_supported_rounded,
                        size: 100,
                        color: Color(0xFF94A3B8),
                      ),
                    ),
                    const SizedBox(height: 24),
                  ],
                ),
              ),
            );
          },
        ),
      ),
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
          color: Color(0xFFF8FAFC),
          image: DecorationImage(
            image: NetworkImage('https://www.transparenttextures.com/patterns/cubes.png'), // Subtle clean texture overlay (optional)
            opacity: 0.05,
            repeat: ImageRepeat.repeat,
          ),
        ),
        child: SafeArea(
          child: Center(
            child: SingleChildScrollView(
              padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 32),
              child: ConstrainedBox(
                constraints: const BoxConstraints(maxWidth: 400),
                child: Container(
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(28),
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withValues(alpha: 0.04),
                        blurRadius: 32,
                        offset: const Offset(0, 12),
                        spreadRadius: -4,
                      ),
                      BoxShadow(
                        color: Colors.black.withValues(alpha: 0.02),
                        blurRadius: 8,
                        offset: const Offset(0, 4),
                        spreadRadius: -2,
                      ),
                    ],
                  ),
                  child: Padding(
                    padding: const EdgeInsets.fromLTRB(32, 40, 32, 40),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Container(
                          width: 64,
                          height: 64,
                          decoration: BoxDecoration(
                            color: const Color(0xFFEFF6FF), // Soft Blue
                            borderRadius: BorderRadius.circular(16),
                          ),
                          child: ClipRRect(
                            borderRadius: BorderRadius.circular(12),
                            child: Image.asset(
                              'assets/images/poolsice.png',
                              fit: BoxFit.contain,
                              errorBuilder: (context, error, stackTrace) => const Icon(
                                Icons.local_shipping_rounded,
                                color: Color(0xFF2563EB),
                                size: 32,
                              ),
                            ),
                          ),
                        ),
                        const SizedBox(height: 24),
                        const Text(
                          'Login\nAplikasi Supir Pools Ice',
                          style: TextStyle(
                            fontSize: 26,
                            fontWeight: FontWeight.w800,
                            height: 1.2,
                            letterSpacing: -0.5,
                            color: Color(0xFF0F172A),
                          ),
                        ),
                        const SizedBox(height: 8),
                        const Text(
                          'Silahkan masuk untuk memantau order dan stok harian.',
                          style: TextStyle(
                            color: Color(0xFF64748B),
                            fontSize: 15,
                            height: 1.4,
                          ),
                        ),
                        const SizedBox(height: 32),
                        TextField(
                          controller: _usernameController,
                          decoration: const InputDecoration(
                            labelText: 'Username',
                            prefixIcon: Icon(Icons.person_outline_rounded, color: Color(0xFF94A3B8)),
                          ),
                        ),
                        const SizedBox(height: 16),
                        TextField(
                          controller: _passwordController,
                          obscureText: true,
                          decoration: const InputDecoration(
                            labelText: 'Password',
                            prefixIcon: Icon(Icons.lock_outline_rounded, color: Color(0xFF94A3B8)),
                          ),
                        ),
                        const SizedBox(height: 32),
                        SizedBox(
                          width: double.infinity,
                          child: FilledButton(
                            onPressed: _isSubmitting ? null : _submitLogin,
                            style: FilledButton.styleFrom(
                              backgroundColor: const Color(0xFF2563EB),
                              foregroundColor: Colors.white,
                              disabledBackgroundColor: const Color(0xFF94A3B8),
                            ),
                            child: _isSubmitting
                                ? const SizedBox(
                                    width: 24,
                                    height: 24,
                                    child: CircularProgressIndicator(
                                      strokeWidth: 2.5,
                                      color: Colors.white,
                                    ),
                                  )
                                : const Text('Login Supir'),
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

class _DriverHomeScreenState extends State<DriverHomeScreen>
  with WidgetsBindingObserver {
  final List<Map<String, dynamic>> _orders = [];
  final Set<int> _notifiedOrderIds = <int>{};
  Timer? _timer;
  bool _isOnline = true;
  bool _isLoading = false;
  bool _isLoadingTodayStock = false;
  bool _isSubmittingStock = false;
  bool _isLoggingOut = false;
  bool _isSessionExpiredHandled = false;
  bool _isLoadingIceTypes = false;
  final Set<int> _isUpdatingOrderIds = <int>{};
  int _todayStock5Kg = 0;
  int _todayStock20Kg = 0;
  bool _hasTodayStockInput = false;
  String _selectedFilterDate = '';

  // FCM
  StreamSubscription<RemoteMessage>? _fcmSubscription;

  // Dynamic ice types
  List<Map<String, dynamic>> _iceTypes = [];
  final Map<int, TextEditingController> _stockControllers = {};
  final Map<int, int> _todayStockByIceTypeId = {};

  final _stock5KgController = TextEditingController();
  final _stock20KgController = TextEditingController();

  @override
  void initState() {
    super.initState();
    _selectedFilterDate = _formatDateYmd(DateTime.now());
    WidgetsBinding.instance.addObserver(this);
    _loadIceTypes();
    _startPolling();
    _fetchTodayStock();
    _registerFcmToken();
    _setupForegroundFcmListener();
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    _timer?.cancel();
    _fcmSubscription?.cancel();
    _stock5KgController.dispose();
    _stock20KgController.dispose();
    for (var controller in _stockControllers.values) {
      controller.dispose();
    }
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      _loadIceTypes();
      _fetchOrders();
      _fetchTodayStock();
      _startPolling();
      return;
    }

    if (state == AppLifecycleState.paused || state == AppLifecycleState.inactive) {
      _timer?.cancel();
    }
  }

  /// Ambil FCM token dari Firebase dan kirim ke server untuk disimpan.
  /// Dipanggil saat login dan saat token diperbarui.
  Future<void> _registerFcmToken() async {
    try {
      final messaging = FirebaseMessaging.instance;

      // Minta izin notifikasi (diperlukan untuk iOS, opsional Android 13+)
      await messaging.requestPermission(
        alert: true,
        badge: true,
        sound: true,
      );

      final token = await messaging.getToken();
      if (token == null || token.isEmpty) {
        return;
      }

      // Kirim token ke server
      final uri = Uri.parse('$apiBaseUrl/driver/fcm-token');
      await http.post(
        uri,
        headers: _authHeaders(json: true),
        body: jsonEncode({'fcm_token': token}),
      );

      // Pantau perubahan token (FCM bisa memperbarui token secara berkala)
      messaging.onTokenRefresh.listen((newToken) async {
        if (!mounted) return;
        final refreshUri = Uri.parse('$apiBaseUrl/driver/fcm-token');
        await http.post(
          refreshUri,
          headers: _authHeaders(json: true),
          body: jsonEncode({'fcm_token': newToken}),
        );
      });
    } catch (_) {
      // Gagal diam-diam — tidak mengganggu flow utama
    }
  }

  /// Setup listener untuk pesan FCM saat app FOREGROUND (terbuka).
  /// FCM tidak menampilkan notifikasi otomatis saat foreground di Android,
  /// jadi kita tampilkan sendiri via flutter_local_notifications.
  void _setupForegroundFcmListener() {
    _fcmSubscription = FirebaseMessaging.onMessage.listen((RemoteMessage message) async {
      final notification = message.notification;
      final data = message.data;

      final title = notification?.title ?? data['title'] ?? '🛵 Order Baru';
      final body  = notification?.body  ?? data['body']  ?? 'Ada pesanan baru masuk!';

      await localNotifications.show(
        message.hashCode,
        title,
        body,
        const NotificationDetails(
          android: AndroidNotificationDetails(
            'driver_orders',
            'Order Masuk',
            channelDescription: 'Notifikasi order baru untuk supir Pools Ice',
            importance: Importance.max,
            priority: Priority.high,
            playSound: true,
            icon: '@mipmap/ic_launcher',
          ),
        ),
      );

      // Juga refresh daftar order supaya tampilan langsung update
      _fetchOrders();
    });
  }

  Future<void> _loadIceTypes() async {
    if (_isLoadingIceTypes) {
      return;
    }

    setState(() {
      _isLoadingIceTypes = true;
    });

    try {
      final uri = Uri.parse('$apiBaseUrl/ice-types');
      final response = await http.get(uri);

      if (response.statusCode != 200) {
        throw Exception('Failed to load ice types');
      }

      final payload = jsonDecode(response.body) as Map<String, dynamic>;
      final data = (payload['data'] as List<dynamic>? ?? [])
          .whereType<Map<String, dynamic>>()
          .toList();

      if (!mounted) {
        return;
      }

      setState(() {
        _iceTypes = data;
        // Initialize controllers for each ice type
        for (final iceType in _iceTypes) {
          final id = iceType['id'] as int?;
          if (id != null && !_stockControllers.containsKey(id)) {
            _stockControllers[id] = TextEditingController();
          }
        }
        _syncTodayStockIntoControllers();
      });
    } catch (e) {
      if (!mounted) {
        return;
      }
      // Silently fail - will retry on refresh
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Gagal ambil daftar jenis es: $e')),
      );
    } finally {
      if (mounted) {
        setState(() {
          _isLoadingIceTypes = false;
        });
      }
    }
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

    final qtyAny = RegExp(r'\b(\d{1,3})\s*(?:pcs|pc|buah|biji|psc|pieces|pca|pck)\b', caseSensitive: false).firstMatch(source);
    if (qtyAny != null) {
      final qty = qtyAny.group(1) ?? '1';
      final weightMatch = RegExp(r'\b(\d{1,3})\s?(?:kg|kilo)\b', caseSensitive: false).firstMatch(source);
      if (weightMatch != null) {
        return '${weightMatch.group(1)}kg - $qty pcs';
      }
    }

    final parts = <String>[];
    for (final weight in [5, 10, 15, 20, 25, 30]) {
      final qty = _extractQtyByWeight(source, weight);
      if (qty != null) {
        parts.add('${weight}kg - $qty''pcs');
      }
    }

    if (parts.isNotEmpty) {
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

      final stockMap = <int, int>{};
      final stocks = (data['stocks'] as List<dynamic>? ?? []).whereType<Map<String, dynamic>>();
      for (final stock in stocks) {
        final iceTypeId = (stock['id'] as num?)?.toInt() ?? (stock['ice_type_id'] as num?)?.toInt();
        final quantity = (stock['quantity'] as num?)?.toInt() ?? 0;
        if (iceTypeId != null) {
          stockMap[iceTypeId] = quantity;
        }
      }

      if (!mounted) {
        return;
      }

      setState(() {
        _todayStock5Kg = (data['stock_5kg'] as num?)?.toInt() ?? 0;
        _todayStock20Kg = (data['stock_20kg'] as num?)?.toInt() ?? 0;
        _todayStockByIceTypeId
          ..clear()
          ..addAll(stockMap);
        _hasTodayStockInput = data['has_stock_input'] == true || _todayStockByIceTypeId.isNotEmpty;
        _syncTodayStockIntoControllers();
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

  void _syncTodayStockIntoControllers() {
    // Jangan overwrite input jika supir belum submit stock (masih dalam mode input).
    if (!_hasTodayStockInput || _iceTypes.isEmpty || _stockControllers.isEmpty) {
      return;
    }

    for (final iceType in _iceTypes) {
      final id = iceType['id'] as int?;
      if (id == null || !_stockControllers.containsKey(id)) {
        continue;
      }

      int quantity = _todayStockByIceTypeId[id] ?? 0;
      if (quantity == 0) {
        final weight = (iceType['weight'] as num?)?.toDouble() ?? 0;
        if ((weight - 5).abs() < 0.01) {
          quantity = _todayStock5Kg;
        } else if ((weight - 20).abs() < 0.01) {
          quantity = _todayStock20Kg;
        }
      }

      _stockControllers[id]!.text = quantity > 0 ? quantity.toString() : '';
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

    // Collect stocks from controllers
    final stocks = <Map<String, dynamic>>[];
    for (final iceType in _iceTypes) {
      final id = iceType['id'] as int?;
      if (id != null && _stockControllers.containsKey(id)) {
        final qty = int.tryParse(_stockControllers[id]!.text.trim());
        if (qty != null && qty >= 0) {
          stocks.add({
            'ice_type_id': id,
            'quantity': qty,
          });
        }
      }
    }

    if (stocks.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Masukkan stok untuk minimal satu jenis es.')),
      );
      return;
    }

    setState(() {
      _isSubmittingStock = true;
    });

    try {
      final uri = Uri.parse('$apiBaseUrl/driver/stocks');
      final response = await http.post(
        uri,
        headers: _authHeaders(json: true),
        body: jsonEncode({
          'stocks': stocks,
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
    _loadIceTypes();
    _fetchOrders();
    _fetchTodayStock();
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
        final orderZone = (order['zone'] as String? ?? '').toLowerCase();

        final sameZone = orderZone == widget.zone.toLowerCase();
        
        if (!sameZone || orderId == 0) {
          continue;
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
      
      _fetchTodayStock();
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
      
      _fetchTodayStock();
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
      _loadIceTypes(),
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

    final itemLabel = (order['items_display'] as String?)?.trim().isNotEmpty == true
      ? order['items_display'].toString()
      : _formatOrderItems(order['items']);

    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(24),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.03),
            blurRadius: 20,
            offset: const Offset(0, 10),
            spreadRadius: -4,
          ),
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.01),
            blurRadius: 6,
            offset: const Offset(0, 2),
            spreadRadius: -2,
          ),
        ],
      ),
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              crossAxisAlignment: CrossAxisAlignment.center,
              children: [
                Container(
                  width: 40,
                  height: 40,
                  alignment: Alignment.center,
                  decoration: BoxDecoration(
                    color: const Color(0xFFF1F5F9),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Text(
                    '#$orderId',
                    style: const TextStyle(
                      color: Color(0xFF475569),
                      fontSize: 14,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                ),
                const SizedBox(width: 14),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        order['customer_name']?.toString().trim().isNotEmpty == true
                            ? order['customer_name'].toString()
                            : 'Pelanggan',
                        style: const TextStyle(
                          color: Color(0xFF0F172A),
                          fontWeight: FontWeight.w800,
                          fontSize: 16,
                          letterSpacing: -0.2,
                        ),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                      const SizedBox(height: 4),
                      Row(
                        children: [
                          const Icon(Icons.location_on_rounded, size: 12, color: Color(0xFF94A3B8)),
                          const SizedBox(width: 4),
                          Text(
                            'Zona ${order['zone'] ?? '-'}',
                            style: const TextStyle(color: Color(0xFF64748B), fontSize: 13, fontWeight: FontWeight.w500),
                          ),
                        ],
                      ),
                      const SizedBox(height: 4),
                      Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Icon(Icons.home_rounded, size: 12, color: Color(0xFF94A3B8)),
                          const SizedBox(width: 4),
                          Expanded(
                            child: Text(
                              (order['customer_address'] as String?)?.trim().isNotEmpty == true
                                  ? order['customer_address'].toString()
                                  : 'Alamat belum tersedia',
                              style: const TextStyle(
                                color: Color(0xFF64748B),
                                fontSize: 12,
                                fontWeight: FontWeight.w500,
                              ),
                              maxLines: 2,
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                  decoration: BoxDecoration(
                    color: _statusBgColor(status, isClaimedByOtherDriver),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: Text(
                    _statusLabel(status, isClaimedByOtherDriver),
                    style: TextStyle(
                      color: _statusTextColor(status, isClaimedByOtherDriver),
                      fontWeight: FontWeight.w800,
                      fontSize: 12,
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 20),
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: const Color(0xFFF8FAFC),
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: const Color(0xFFE2E8F0)),
              ),
              child: Row(
                children: [
                  const Icon(Icons.shopping_bag_rounded, size: 18, color: Color(0xFF64748B)),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Text(
                      itemLabel,
                      style: const TextStyle(fontWeight: FontWeight.w700, color: Color(0xFF334155), fontSize: 14),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 20),
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
                        foregroundColor: const Color(0xFFDC2626),
                        side: const BorderSide(color: Color(0xFFFECACA), width: 1.5),
                        padding: const EdgeInsets.symmetric(vertical: 14),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                      child: const Text('Tolak', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 15)),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: FilledButton(
                      onPressed: isUpdating || orderId == 0
                          ? null
                          : () => _confirmAndUpdateOrderStatus(
                                orderId: orderId,
                                status: 'approved',
                              ),
                      style: FilledButton.styleFrom(
                        backgroundColor: const Color(0xFF2563EB),
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(vertical: 14),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                      child: Text(
                        isUpdating ? 'Proses...' : 'Terima',
                        style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 15),
                      ),
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
                    padding: const EdgeInsets.symmetric(vertical: 16),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                  ),
                  icon: const Icon(Icons.check_circle_rounded, size: 20),
                  label: Text(
                    isUpdating ? 'Memproses Selesai...' : 'Konfirmasi Selesai Antar',
                    style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 15),
                  ),
                ),
              )
            else
              Container(
                width: double.infinity,
                padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 16),
                decoration: BoxDecoration(
                  color: isCompleted ? const Color(0xFFEFF6FF) : const Color(0xFFF1F5F9),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Row(
                  children: [
                    Icon(
                      isCompleted ? Icons.check_circle_rounded : Icons.info_rounded,
                      size: 18,
                      color: isCompleted ? const Color(0xFF2563EB) : const Color(0xFF64748B),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Text(
                        isClaimedByOtherDriver
                            ? 'Order ditangani oleh supir lain'
                            : isApprovedByOtherDriver
                            ? 'Order sudah diterima supir lain'
                            : isCompleted
                            ? 'Pengantaran order telah selesai'
                            : 'Order sudah diproses',
                        style: TextStyle(
                          color: isCompleted ? const Color(0xFF1E3A8A) : const Color(0xFF475569),
                          fontWeight: FontWeight.w600,
                          fontSize: 13,
                        ),
                      ),
                    ),
                  ],
                ),
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
            Text(
              widget.driverName,
              style: const TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.w800,
                letterSpacing: -0.5,
              ),
            ),
            const SizedBox(height: 2),
            Row(
              children: [
                const Icon(Icons.location_on, size: 12, color: Color(0xFF64748B)),
                const SizedBox(width: 4),
                Text(
                  'Zona ${widget.zone}',
                  style: const TextStyle(fontSize: 13, color: Color(0xFF64748B), fontWeight: FontWeight.w500),
                ),
              ],
            ),
          ],
        ),
        actions: [
          Container(
            margin: const EdgeInsets.only(right: 8),
            padding: const EdgeInsets.fromLTRB(14, 4, 8, 4),
            decoration: BoxDecoration(
              color: _isOnline
                  ? const Color(0xFFEFF6FF)
                  : const Color(0xFFF1F5F9),
              borderRadius: BorderRadius.circular(999),
            ),
            child: Row(
              children: [
                Container(
                  width: 8,
                  height: 8,
                  decoration: BoxDecoration(
                    color: _isOnline ? const Color(0xFF2563EB) : const Color(0xFF94A3B8),
                    shape: BoxShape.circle,
                  ),
                ),
                const SizedBox(width: 6),
                Text(
                  _isOnline ? 'Online' : 'Offline',
                  style: TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.w700,
                    color: _isOnline ? const Color(0xFF1E3A8A) : const Color(0xFF475569),
                  ),
                ),
                const SizedBox(width: 4),
                Switch(
                  value: _isOnline,
                  activeColor: const Color(0xFF2563EB),
                  activeTrackColor: const Color(0xFFBFDBFE),
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
                    width: 20,
                    height: 20,
                    child: CircularProgressIndicator(strokeWidth: 2.5),
                  )
                : const Icon(Icons.logout_rounded, color: Color(0xFF64748B)),
          ),
          const SizedBox(width: 8),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: _refreshAll,
        color: const Color(0xFF2563EB),
        child: ListView(
          padding: const EdgeInsets.fromLTRB(16, 20, 16, 32),
          children: [
            Container(
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(24),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: 0.03),
                    blurRadius: 20,
                    offset: const Offset(0, 10),
                    spreadRadius: -4,
                  ),
                ],
              ),
              child: Padding(
                padding: const EdgeInsets.all(20),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.all(10),
                          decoration: BoxDecoration(
                            color: const Color(0xFFEFF6FF),
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: const Icon(Icons.inventory_2_rounded, size: 20, color: Color(0xFF2563EB)),
                        ),
                        const SizedBox(width: 12),
                        const Expanded(
                          child: Text(
                            'Stok Bawaan Hari Ini',
                            style: TextStyle(fontWeight: FontWeight.w700, fontSize: 16),
                          ),
                        ),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                          decoration: BoxDecoration(
                            color: const Color(0xFFF1F5F9),
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: Text(
                            _formatDateYmd(DateTime.now()),
                            style: const TextStyle(
                              fontSize: 12,
                              fontWeight: FontWeight.w600,
                              color: Color(0xFF475569),
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 20),
                    if (_isLoadingIceTypes)
                      const Center(child: Padding(
                        padding: EdgeInsets.symmetric(vertical: 24),
                        child: CircularProgressIndicator(strokeWidth: 3),
                      ))
                    else if (_iceTypes.isEmpty)
                      Container(
                        width: double.infinity,
                        padding: const EdgeInsets.all(16),
                        decoration: BoxDecoration(
                          color: const Color(0xFFF8FAFC),
                          borderRadius: BorderRadius.circular(16),
                          border: Border.all(color: const Color(0xFFE2E8F0)),
                        ),
                        child: const Text(
                          'Belum ada jenis es yang tersedia.',
                          textAlign: TextAlign.center,
                          style: TextStyle(color: Color(0xFF64748B)),
                        ),
                      )
                    else
                      Column(
                        children: [
                          ..._iceTypes.map((iceType) {
                            final id = iceType['id'] as int? ?? 0;
                            final name = (iceType['name'] as String? ?? 'Es').trim();
                            final weight = iceType['weight'] as dynamic;
                            final weightStr = weight is int
                                ? '$weight kg'
                                : '${(weight as double?)?.toStringAsFixed(1) ?? '?'} kg';

                            return Padding(
                              padding: const EdgeInsets.only(bottom: 14),
                              child: TextField(
                                controller: _stockControllers[id],
                                enabled: !_hasTodayStockInput,
                                keyboardType: TextInputType.number,
                                decoration: InputDecoration(
                                  labelText: 'Stok $name ($weightStr)',
                                  prefixIcon: const Icon(Icons.ac_unit_rounded, color: Color(0xFF94A3B8)),
                                  hintText: '0',
                                ),
                              ),
                            );
                          }).toList(),
                          const SizedBox(height: 8),
                          if (_isLoadingTodayStock)
                            const LinearProgressIndicator(minHeight: 3, borderRadius: BorderRadius.all(Radius.circular(2)))
                          else
                            Row(
                              children: [
                                Icon(
                                  _hasTodayStockInput ? Icons.check_circle_rounded : Icons.info_rounded,
                                  size: 16,
                                  color: _hasTodayStockInput ? const Color(0xFF10B981) : const Color(0xFF94A3B8),
                                ),
                                const SizedBox(width: 8),
                                Expanded(
                                  child: Text(
                                    _hasTodayStockInput
                                        ? 'Stok sudah dicatat dan akan terupdate otomatis.'
                                        : 'Belum ada input stok untuk hari ini.',
                                    style: TextStyle(
                                      color: _hasTodayStockInput ? const Color(0xFF047857) : const Color(0xFF64748B),
                                      fontSize: 13,
                                      fontWeight: FontWeight.w500,
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          const SizedBox(height: 20),
                          SizedBox(
                            width: double.infinity,
                            child: FilledButton.icon(
                              onPressed: (_isSubmittingStock || _hasTodayStockInput || _iceTypes.isEmpty)
                                  ? null
                                  : _submitDriverStock,
                              icon: _isSubmittingStock
                                  ? const SizedBox(
                                      width: 20,
                                      height: 20,
                                      child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                                    )
                                  : const Icon(Icons.save_rounded, size: 20),
                              label: Text(
                                _isSubmittingStock
                                    ? 'Menyimpan...'
                                    : (_hasTodayStockInput
                                        ? 'Stok Hari Ini Sudah Diinput'
                                        : 'Simpan Stok'),
                              ),
                              style: FilledButton.styleFrom(
                                backgroundColor: const Color(0xFF2563EB),
                                disabledBackgroundColor: const Color(0xFFE2E8F0),
                                disabledForegroundColor: const Color(0xFF94A3B8),
                                padding: const EdgeInsets.symmetric(vertical: 14),
                              ),
                            ),
                          ),
                        ],
                      ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 28),
            Row(
              children: [
                const Icon(Icons.receipt_long_rounded, color: Color(0xFF64748B), size: 24),
                const SizedBox(width: 8),
                const Text(
                  'Daftar Order Aktif',
                  style: TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.w800,
                    letterSpacing: -0.5,
                    color: Color(0xFF0F172A),
                  ),
                ),
                const Spacer(),
                if (_isLoading)
                  const SizedBox(
                    width: 18,
                    height: 18,
                    child: CircularProgressIndicator(strokeWidth: 2.5),
                  ),
              ],
            ),
            const SizedBox(height: 16),
            ...(() {
              final List<DateTime> sortedDates = [];
              final today = DateTime.now();
              for (int i = 0; i < 30; i++) {
                sortedDates.add(today.subtract(Duration(days: i)));
              }

              return [
                SizedBox(
                  height: 80,
                  child: ListView.separated(
                    scrollDirection: Axis.horizontal,
                    itemCount: sortedDates.length,
                    separatorBuilder: (context, index) => const SizedBox(width: 8),
                    itemBuilder: (context, index) {
                      final dateObj = sortedDates[index];
                      final dateStr = _formatDateYmd(dateObj);
                      final isSelected = dateStr == _selectedFilterDate;
                      
                      String getDayInitials(int weekday) {
                        switch (weekday) {
                          case 1: return 'Sen';
                          case 2: return 'Sel';
                          case 3: return 'Rab';
                          case 4: return 'Kam';
                          case 5: return 'Jum';
                          case 6: return 'Sab';
                          case 7: return 'Min';
                          default: return '';
                        }
                      }
                      
                      final dayName = getDayInitials(dateObj.weekday);
                      final dateNum = dateObj.day.toString();
                      
                      return GestureDetector(
                        onTap: () {
                          setState(() {
                            _selectedFilterDate = dateStr;
                          });
                        },
                        child: Container(
                          width: 52,
                          padding: const EdgeInsets.symmetric(vertical: 6),
                          decoration: BoxDecoration(
                            color: isSelected ? Colors.white : const Color(0xFFF8FAFC),
                            borderRadius: BorderRadius.circular(26),
                            border: Border.all(
                              color: isSelected ? const Color(0xFF2563EB) : Colors.transparent,
                              width: 1.5,
                            ),
                          ),
                          child: Column(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              Text(
                                dayName,
                                style: const TextStyle(
                                  color: Color(0xFF475569),
                                  fontWeight: FontWeight.w700,
                                  fontSize: 12,
                                ),
                              ),
                              const SizedBox(height: 6),
                              Container(
                                width: 34,
                                height: 34,
                                decoration: BoxDecoration(
                                  color: isSelected ? const Color(0xFF2563EB) : const Color(0xFFE2E8F0),
                                  shape: BoxShape.circle,
                                ),
                                alignment: Alignment.center,
                                child: Text(
                                  dateNum,
                                  style: TextStyle(
                                    color: isSelected ? Colors.white : const Color(0xFF64748B),
                                    fontWeight: FontWeight.w800,
                                    fontSize: 14,
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ),
                      );
                    },
                  ),
                ),
                const SizedBox(height: 16),
              ];
            })(),
            ...(() {
              final filteredOrders = _orders.where((order) {
                if (order['created_at'] != null) {
                  try {
                    final dt = DateTime.parse(order['created_at'].toString());
                    return _formatDateYmd(dt) == _selectedFilterDate;
                  } catch (_) {}
                }
                return false;
              }).toList();

              if (filteredOrders.isEmpty) {
                return [
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.all(32),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(24),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withValues(alpha: 0.03),
                          blurRadius: 20,
                          offset: const Offset(0, 10),
                          spreadRadius: -4,
                        ),
                      ],
                    ),
                    child: Column(
                      children: [
                        Icon(Icons.inbox_rounded, size: 48, color: const Color(0xFF94A3B8).withValues(alpha: 0.5)),
                        const SizedBox(height: 16),
                        const Text(
                          'Belum Ada Order',
                          style: TextStyle(fontSize: 16, fontWeight: FontWeight.w700, color: Color(0xFF475569)),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          _selectedFilterDate == _formatDateYmd(DateTime.now())
                              ? 'Pastikan status Anda Online untuk menerima order.'
                              : 'Tidak ada order pada tanggal ini.',
                          textAlign: TextAlign.center,
                          style: const TextStyle(color: Color(0xFF94A3B8), fontSize: 13),
                        ),
                      ],
                    ),
                  )
                ];
              }

              return filteredOrders.map((order) => Padding(
                padding: const EdgeInsets.only(bottom: 16),
                child: _buildOrderCard(order),
              )).toList();
            })(),
          ],
        ),
      ),
    );
  }
}
