// This is a basic Flutter widget test.
//
// To perform an interaction with a widget in your test, use the WidgetTester
// utility in the flutter_test package. For example, you can send tap and scroll
// gestures. You can also use WidgetTester to find child widgets in the widget
// tree, read text, and verify that the values of widget properties are correct.

import 'package:flutter_test/flutter_test.dart';

import 'package:driver_app_flutter/main.dart';

void main() {
  testWidgets('Login form is visible', (WidgetTester tester) async {
    await tester.pumpWidget(const DriverApp());

    expect(find.text('Masuk Supir'), findsOneWidget);
    expect(find.text('Login Supir'), findsOneWidget);
  });
}
