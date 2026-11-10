import 'package:flutter/material.dart';

class AppTheme {
  static const primary = Color(0xFF2563EB);
  static const darkBackground = Color(0xFF0B1220);
  static const darkSurface = Color(0xFF111827);

  static ThemeData get light => _theme(Brightness.light, const Color(0xFFF8FAFC), Colors.white, const Color(0xFF0F172A));
  static ThemeData get dark => _theme(Brightness.dark, darkBackground, darkSurface, const Color(0xFFF8FAFC));

  static ThemeData _theme(Brightness brightness, Color background, Color surface, Color text) {
    final scheme = ColorScheme.fromSeed(seedColor: primary, brightness: brightness).copyWith(primary: brightness == Brightness.dark ? const Color(0xFF60A5FA) : primary, surface: surface);
    return ThemeData(useMaterial3: true, brightness: brightness, colorScheme: scheme, scaffoldBackgroundColor: background, fontFamily: 'Inter', textTheme: ThemeData(brightness: brightness).textTheme.apply(fontFamily: 'Inter', bodyColor: text, displayColor: text), inputDecorationTheme: const InputDecorationTheme(border: OutlineInputBorder(), filled: true, contentPadding: EdgeInsets.symmetric(horizontal: 14, vertical: 13)), cardTheme: const CardThemeData(elevation: 0, margin: EdgeInsets.zero));
  }
}
