# iOS configuration checklist

Generate the native iOS project with `flutter create .` on macOS/Xcode. Configure the bundle identifier, Face ID usage description, camera usage description, notification permission, secure Keychain storage, app icons and signing placeholders. Do not commit certificates or provisioning profiles.

Validate with `flutter build ios --no-codesign` before configuring distribution signing.
