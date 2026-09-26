import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:nasabah/core/theme/app_theme.dart';

void main() {
  testWidgets('renders a themed placeholder', (tester) async {
    await tester.pumpWidget(
      MaterialApp(
        theme: AppTheme.light(),
        home: const Scaffold(body: Text('Bank Mini')),
      ),
    );

    expect(find.text('Bank Mini'), findsOneWidget);
  });
}
