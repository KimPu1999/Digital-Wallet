# Android configuration checklist

Generate the native Android project with `flutter create .` after installing Flutter. Configure the final application ID, app icons, camera permission, biometric/device authentication, notification permission and release signing through CI secrets. Do not commit `local.properties`, keystores or signing credentials.

The debug API URL should be supplied with `--dart-define=API_BASE_URL=http://10.0.2.2:8000/api/v1` for an Android emulator.
