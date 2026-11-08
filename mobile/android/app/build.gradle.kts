plugins {
    id("com.android.application")
    id("kotlin-android")
    // The Flutter Gradle Plugin must be applied after the Android and Kotlin Gradle plugins.
    id("dev.flutter.flutter-gradle-plugin")
}

android {
    namespace = "id.sch.bankmini.nasabah"
    compileSdk = flutter.compileSdkVersion
    ndkVersion = flutter.ndkVersion

    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_17
        targetCompatibility = JavaVersion.VERSION_17
    }

    kotlinOptions {
        jvmTarget = JavaVersion.VERSION_17.toString()
    }

    defaultConfig {
        // TODO: Specify your own unique Application ID (https://developer.android.com/studio/build/application-id.html).
        applicationId = "id.sch.bankmini.nasabah"
        // You can update the following values to match your application needs.
        // For more information, see: https://flutter.dev/to/review-gradle-config.
        minSdk = flutter.minSdkVersion
        targetSdk = flutter.targetSdkVersion
        versionCode = flutter.versionCode
        versionName = flutter.versionName
    }

    buildTypes {
        release {
            // TODO: Add your own signing config for the release build.
            // Signing with the debug keys for now, so `flutter run --release` works.
            signingConfig = signingConfigs.getByName("debug")
        }
    }
}

flutter {
    source = "../.."
}

val flutterApkDirectory = layout.buildDirectory.dir("outputs/flutter-apk")
val copyReleaseApks = tasks.register("copyReleaseApks") {
    doLast {
        val outputDirectory = flutterApkDirectory.get().asFile
        val releaseApk = outputDirectory.resolve("app-release.apk")
        if (releaseApk.isFile) {
            releaseApk.copyTo(
                target = outputDirectory.resolve("Bank Mini Skansaba.apk"),
                overwrite = true,
            )

            val landingApk = rootProject.file(
                "../../public/unduhan/bank-mini-nasabah.apk",
            )
            landingApk.parentFile.mkdirs()
            releaseApk.copyTo(target = landingApk, overwrite = true)
        }
    }
}

tasks.configureEach {
    if (name == "assembleRelease") {
        finalizedBy(copyReleaseApks)
    }
}
