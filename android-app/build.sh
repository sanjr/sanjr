#!/usr/bin/env bash
# Builds the ITI Exam Android app without Gradle: a signed APK (install/share directly) and a signed AAB (Play Store).
#
# Needs: JDK 17+, python3, and in $TOOLS: aapt2, dx.jar (dalvik-dx), bundletool.jar; plus android.jar (platform 36)
# Signing: KEYSTORE=/path/itiexam-release.p12 KEY_ALIAS=itiexam KEY_PASS=… ./build.sh
# Android Studio users can instead open this folder and use Build → Generate Signed Bundle / APK.
set -euo pipefail
cd "$(dirname "$0")"

TOOLS=${TOOLS:-/opt/abt}
ANDROID_JAR=${ANDROID_JAR:-/opt/android-sdk/platforms/android-36/android.jar}
: "${KEYSTORE:?set KEYSTORE}" "${KEY_ALIAS:=itiexam}" "${KEY_PASS:?set KEY_PASS}"

PKG=com.itiexam.app
VERSION_CODE=$(sed -n 's/.*versionCode \([0-9]*\).*/\1/p' app/build.gradle)
VERSION_NAME=$(sed -n 's/.*versionName "\(.*\)".*/\1/p' app/build.gradle)
SRC=app/src/main
OUT=build
rm -rf "$OUT" && mkdir -p "$OUT"/{gen,classes,module/manifest,module/dex,tools}

grep -q "VERSION_NAME = \"$VERSION_NAME\"" $SRC/java/com/itiexam/app/BuildInfo.java || { echo "BuildInfo.VERSION_NAME must be $VERSION_NAME"; exit 1; }

echo "== resources"
# the manifest in the source has no package (Gradle sets it); aapt2 needs it
sed "s|<manifest xmlns:android=\"http://schemas.android.com/apk/res/android\">|<manifest xmlns:android=\"http://schemas.android.com/apk/res/android\" package=\"$PKG\">|" $SRC/AndroidManifest.xml > $OUT/AndroidManifest.xml
"$TOOLS/aapt2" compile --dir $SRC/res -o $OUT/res.zip
LINK=(-I "$ANDROID_JAR" --manifest $OUT/AndroidManifest.xml --min-sdk-version 24 --target-sdk-version 36
      --version-code "$VERSION_CODE" --version-name "$VERSION_NAME" --auto-add-overlay $OUT/res.zip)
"$TOOLS/aapt2" link "${LINK[@]}" --java $OUT/gen -o $OUT/base.ap_
"$TOOLS/aapt2" link "${LINK[@]}" --proto-format -o $OUT/proto.zip

echo "== java"
javac -nowarn -Xlint:-options -source 8 -target 8 -bootclasspath "$ANDROID_JAR" -encoding UTF-8 -d $OUT/classes \
  $(find $OUT/gen $SRC/java -name '*.java')
java -cp "$TOOLS/dx.jar" com.android.dx.command.Main --dex --min-sdk-version=24 --output=$OUT/classes.dex $OUT/classes

echo "== apk"
cp $OUT/base.ap_ $OUT/unsigned.apk
(cd $OUT && zip -q -j unsigned.apk classes.dex)
python3 tools/zipalign.py $OUT/unsigned.apk $OUT/aligned.apk
javac -nowarn -cp "$TOOLS/bundletool.jar" -d $OUT/tools tools/Sign.java    # bundletool carries a current apksig
java -cp "$TOOLS/bundletool.jar:$OUT/tools" Sign $OUT/aligned.apk $OUT/ITIExam-v$VERSION_NAME.apk "$KEYSTORE" "$KEY_ALIAS" "$KEY_PASS"
python3 tools/zipalign.py $OUT/ITIExam-v$VERSION_NAME.apk - --check

echo "== aab (Play Store)"
(cd $OUT/module && unzip -q ../proto.zip && mv AndroidManifest.xml manifest/ && cp ../classes.dex dex/ && zip -q -r ../module.zip .)
java -jar "$TOOLS/bundletool.jar" build-bundle --modules=$OUT/module.zip --output=$OUT/ITIExam-v$VERSION_NAME.aab
jarsigner -sigalg SHA256withRSA -digestalg SHA-256 -keystore "$KEYSTORE" -storetype PKCS12 -storepass "$KEY_PASS" \
  $OUT/ITIExam-v$VERSION_NAME.aab "$KEY_ALIAS" > /dev/null
java -jar "$TOOLS/bundletool.jar" validate --bundle=$OUT/ITIExam-v$VERSION_NAME.aab > /dev/null && echo "aab valid"

ls -la $OUT/*.apk $OUT/*.aab
