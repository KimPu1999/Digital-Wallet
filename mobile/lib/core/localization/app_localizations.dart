import 'package:flutter/widgets.dart';

class AppLocalizations {
  const AppLocalizations(this.locale);
  final Locale locale;
  static const supportedLocales = [Locale('en'), Locale('my')];
  static const delegate = _Delegate();

  String get appName => locale.languageCode == 'my' ? 'ဒစ်ဂျစ်တယ်ပိုက်ဆံအိတ်' : 'Digital Wallet';
  String get signIn => locale.languageCode == 'my' ? 'ဝင်ရောက်မည်' : 'Sign in';
  String get email => locale.languageCode == 'my' ? 'အီးမေးလ်' : 'Email';
  String get password => locale.languageCode == 'my' ? 'စကားဝှက်' : 'Password';
  String get home => locale.languageCode == 'my' ? 'ပင်မ' : 'Home';
  String get sandboxNotice => locale.languageCode == 'my' ? 'စမ်းသပ်ပတ်ဝန်းကျင် — ငွေအစစ် လွှဲပြောင်းမှု မပြုလုပ်ပါ။' : 'Sandbox mode — no real money movement is enabled.';

  static AppLocalizations of(BuildContext context) => Localizations.of<AppLocalizations>(context, AppLocalizations)!;
}

class _Delegate extends LocalizationsDelegate<AppLocalizations> {
  const _Delegate();
  @override bool isSupported(Locale locale) => ['en', 'my'].contains(locale.languageCode);
  @override Future<AppLocalizations> load(Locale locale) async => AppLocalizations(locale);
  @override bool shouldReload(_Delegate old) => false;
}
