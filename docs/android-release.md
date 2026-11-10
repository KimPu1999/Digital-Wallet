# Android Release Preparation

The repository includes permission examples and a CI debug-build job, but no signing material or generated Android project. Run `flutter create .`, configure a non-production application ID, version code/name, icons, splash screen and permissions, then validate `flutter build apk --debug` and `flutter build appbundle --release` with signing supplied only through protected CI secrets.

Do not upload automatically to Google Play. Sandbox beta distribution requires explicit approval and tester access controls.
