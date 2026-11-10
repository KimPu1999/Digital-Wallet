# iOS Release Preparation

The repository includes Info.plist permission examples and a no-code-sign CI job, but no certificates, profiles or generated Xcode project. Run `flutter create .` on macOS, configure the bundle identifier, version/build number, Face ID/camera/notification descriptions and secure Keychain behavior, then validate `flutter build ios --no-codesign`.

Do not submit automatically to TestFlight or the App Store.
