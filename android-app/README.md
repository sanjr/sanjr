# ITI Exam – Android app

Native Android app (Java, no extra libraries) that shows the ITIExam website with app polish.

## What the app does

- **Intro:** branded animated intro and Android 12+ system splash with the round ITI Exam logo.
- **Page loading:** thin orange-to-blue loading line, and pull down to refresh (switched off on test pages so a running test is never reloaded by mistake).
- **Offline screen:** with a Try Again button; it reloads by itself when the internet comes back.
- **Google login:** opens in a Chrome Custom Tab, because Google does not allow sign-in inside a web view. The website then hands the login back to the app with a one-time code (PKCE-style: only the app that started the login can use it).
- **Results:** test results save to the student's dashboard exactly as on the website.
- **Screen and screen size:** the screen stays on during a CBT test, and the app is edge-to-edge on Android 15/16 with correct status-bar, navigation-bar and keyboard spacing.
- **Downloads and links:** PDFs and files go through Android's download manager. WhatsApp, YouTube, Telegram, Play Store, phone and email links open in their own apps; other outside sites open in a Custom Tab.
- **Website links and shortcuts:** links to the website open in the app (App Links, verified by `/.well-known/assetlinks.json` on the site). Long-pressing the icon gives the shortcuts Mock Tests, My Dashboard and ITI Jobs.
- **Back button:** goes back through pages; pressing it twice on the first page exits. Android 13+ predictive back is supported.
- **AdSense:** hidden inside the app (Google allows ads in apps only through its Mobile Ads SDK).

Minimum Android 7.0 (API 24), target API 36. Package `com.itiexam.app`.

## Change the website address

Edit `BASE_URL` (and `HOSTS` if needed) in `app/src/main/java/com/itiexam/app/Config.java`. Update the hosts in `AndroidManifest.xml` and `res/xml/shortcuts.xml` to match, then raise the version.

## New version

Raise `versionCode` (by 1) and `versionName` in `app/build.gradle`, and `VERSION_NAME` in `BuildInfo.java`.

## Build

**Android Studio:** open this folder and use *Build → Generate Signed Bundle / APK*.

**Command line without Gradle** (used for the first release):

```
KEYSTORE=/path/itiexam-release.p12 KEY_PASS=… ./build.sh
```

`build.sh` needs JDK 17+, python3, the Android platform 36 `android.jar`, and in `$TOOLS`:

- `aapt2`, from apktool-lib 3.0.3 on Maven Central
- `dx.jar`, from `com.jakewharton.android.repackaged:dalvik-dx:16.0.1`
- `bundletool.jar` 1.18.1, from github.com/google/bundletool

It writes `build/ITIExam-v<version>.apk`, signed with v2 and v3 signatures and zip-aligned, and `build/ITIExam-v<version>.aab` for the Play Store.

## Signing key

The release key (`itiexam-release.p12`, alias `itiexam`) is **not** in this repository. Keep it and its password safe: every update must be signed with the same key. On Play Console, turn on *Play App Signing* so that this key becomes the upload key and Google keeps the app signing key.

After the first upload, copy Play Console's *App signing key certificate SHA-256* into the website's **Admin → Settings → Android app**, so website links keep opening in the app.

## Play Store listing files

The `store/` folder holds:

- the 512×512 icon
- the 1024×500 feature graphic
- 1080×1920 phone screenshots
