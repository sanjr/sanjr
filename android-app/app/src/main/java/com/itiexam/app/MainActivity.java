package com.itiexam.app;

import android.app.Activity;
import android.app.DownloadManager;
import android.content.ActivityNotFoundException;
import android.content.Context;
import android.content.Intent;
import android.content.SharedPreferences;
import android.content.pm.PackageManager;
import android.content.pm.ResolveInfo;
import android.graphics.Bitmap;
import android.graphics.Color;
import android.net.ConnectivityManager;
import android.net.Network;
import android.net.Uri;
import android.os.Build;
import android.os.Bundle;
import android.os.Environment;
import android.os.Handler;
import android.os.Looper;
import android.util.Base64;
import android.view.View;
import android.view.ViewGroup;
import android.view.Window;
import android.view.WindowInsets;
import android.view.WindowInsetsController;
import android.view.WindowManager;
import android.view.animation.DecelerateInterpolator;
import android.webkit.CookieManager;
import android.webkit.DownloadListener;
import android.webkit.GeolocationPermissions;
import android.webkit.PermissionRequest;
import android.webkit.URLUtil;
import android.webkit.ValueCallback;
import android.webkit.WebChromeClient;
import android.webkit.WebResourceError;
import android.webkit.WebResourceRequest;
import android.webkit.WebSettings;
import android.webkit.WebView;
import android.webkit.WebViewClient;
import android.webkit.RenderProcessGoneDetail;
import android.widget.FrameLayout;
import android.widget.Toast;
import android.window.OnBackInvokedCallback;
import android.window.OnBackInvokedDispatcher;

import java.security.MessageDigest;
import java.security.SecureRandom;
import java.util.List;

public class MainActivity extends Activity {
    private static final int REQ_FILE = 41;
    private static final String CT_SESSION = "android.support.customtabs.extra.SESSION";

    private WebView web;
    private PullRefreshLayout pull;
    private ProgressLine progress;
    private View splash, offline, content;
    private FrameLayout fullscreen;
    private View customView;
    private WebChromeClient.CustomViewCallback customCallback;
    private ValueCallback<Uri[]> fileCallback;
    private ConnectivityManager.NetworkCallback netCallback;
    private final Handler ui = new Handler(Looper.getMainLooper());
    private boolean mainFrameError, splashGone;
    private long lastBack, splashShownAt;
    private SharedPreferences prefs;

    @Override
    protected void onCreate(Bundle saved) {
        super.onCreate(saved);
        prefs = getSharedPreferences("app", MODE_PRIVATE);
        edgeToEdge();
        setContentView(R.layout.activity_main);

        content = findViewById(R.id.content);
        web = findViewById(R.id.web);
        pull = findViewById(R.id.pull);
        progress = findViewById(R.id.progress);
        offline = findViewById(R.id.offline);
        splash = findViewById(R.id.splash);
        fullscreen = findViewById(R.id.fullscreen);

        applyInsets();
        setUpWebView();
        setUpSplash(saved != null);

        findViewById(R.id.retry).setOnClickListener(new View.OnClickListener() {
            @Override public void onClick(View v) { retry(); }
        });
        pull.setListener(new PullRefreshLayout.Listener() {
            @Override public void onRefresh() { web.reload(); }
        });
        setUpBack();
        watchNetwork();

        if (saved != null && web.restoreState(saved) != null) return;
        if (!handleIntent(getIntent())) web.loadUrl(Config.BASE_URL + "/");
    }

    /* ---------------- window: draw behind the status/navigation bars, light bars with dark icons ---------------- */

    private void edgeToEdge() {
        Window w = getWindow();
        w.setStatusBarColor(Color.TRANSPARENT);
        if (Build.VERSION.SDK_INT >= 30) {
            w.setDecorFitsSystemWindows(false);
            w.setNavigationBarColor(Color.TRANSPARENT);
        } else {
            int flags = View.SYSTEM_UI_FLAG_LAYOUT_STABLE | View.SYSTEM_UI_FLAG_LAYOUT_FULLSCREEN | View.SYSTEM_UI_FLAG_LIGHT_STATUS_BAR;
            if (Build.VERSION.SDK_INT >= 27) {
                flags |= View.SYSTEM_UI_FLAG_LAYOUT_HIDE_NAVIGATION | View.SYSTEM_UI_FLAG_LIGHT_NAVIGATION_BAR;
                w.setNavigationBarColor(Color.TRANSPARENT);
            } else {
                w.setNavigationBarColor(Config.BRAND_NAVY);
            }
            w.getDecorView().setSystemUiVisibility(flags);
        }
        if (Build.VERSION.SDK_INT >= 29) w.setNavigationBarContrastEnforced(false);
    }

    private void lightBars() {
        if (Build.VERSION.SDK_INT >= 30) {
            WindowInsetsController c = getWindow().getInsetsController();
            if (c != null) {
                int light = WindowInsetsController.APPEARANCE_LIGHT_STATUS_BARS | WindowInsetsController.APPEARANCE_LIGHT_NAVIGATION_BARS;
                c.setSystemBarsAppearance(light, light);
            }
        }
    }

    /** Keeps the page clear of the status bar, the navigation bar and the keyboard. */
    private void applyInsets() {
        content.setOnApplyWindowInsetsListener(new View.OnApplyWindowInsetsListener() {
            @Override public WindowInsets onApplyWindowInsets(View v, WindowInsets in) {
                int top, bottom, left, right;
                if (Build.VERSION.SDK_INT >= 30) {
                    android.graphics.Insets bars = in.getInsets(WindowInsets.Type.systemBars() | WindowInsets.Type.displayCutout());
                    android.graphics.Insets ime = in.getInsets(WindowInsets.Type.ime());
                    top = bars.top; left = bars.left; right = bars.right; bottom = Math.max(bars.bottom, ime.bottom);
                } else {
                    top = in.getSystemWindowInsetTop(); bottom = in.getSystemWindowInsetBottom();
                    left = in.getSystemWindowInsetLeft(); right = in.getSystemWindowInsetRight();
                }
                v.setPadding(left, top, right, bottom);
                return in;
            }
        });
        lightBars();
    }

    /* ---------------- the web page ---------------- */

    private void setUpWebView() {
        WebSettings s = web.getSettings();
        s.setJavaScriptEnabled(true);
        s.setDomStorageEnabled(true);
        s.setLoadWithOverviewMode(true);
        s.setUseWideViewPort(true);
        s.setSupportZoom(false);
        s.setBuiltInZoomControls(false);
        s.setDisplayZoomControls(false);
        s.setMixedContentMode(WebSettings.MIXED_CONTENT_NEVER_ALLOW);
        s.setAllowFileAccess(false);
        s.setAllowContentAccess(false);
        s.setGeolocationEnabled(false);
        s.setSupportMultipleWindows(false);
        s.setUserAgentString(s.getUserAgentString() + " " + Config.USER_AGENT_TAG);
        web.setBackgroundColor(Color.WHITE);
        web.setOverScrollMode(View.OVER_SCROLL_NEVER);

        CookieManager cm = CookieManager.getInstance();
        cm.setAcceptCookie(true);
        cm.setAcceptThirdPartyCookies(web, true);

        web.setWebViewClient(new WebViewClient() {
            @Override
            public boolean shouldOverrideUrlLoading(WebView view, WebResourceRequest req) {
                if (!req.isForMainFrame()) return false;          // pages inside a test's frame stay there
                return route(req.getUrl());
            }

            @Override
            public void onPageStarted(WebView view, String url, Bitmap favicon) {
                mainFrameError = false;
                onPathChanged(Uri.parse(url));
            }

            @Override
            public void onPageFinished(WebView view, String url) {
                pull.setRefreshing(false);
                CookieManager.getInstance().flush();
                if (!mainFrameError) {
                    offline.setVisibility(View.GONE);
                    hideSplash();
                }
            }

            @Override
            public void onReceivedError(WebView view, WebResourceRequest req, WebResourceError err) {
                if (!req.isForMainFrame()) return;
                mainFrameError = true;
                pull.setRefreshing(false);
                showOffline();
            }

            @Override
            public boolean onRenderProcessGone(WebView view, RenderProcessGoneDetail detail) {
                recreate();                                       // the page's engine crashed: start the screen again
                return true;
            }
        });

        web.setWebChromeClient(new WebChromeClient() {
            @Override public void onProgressChanged(WebView view, int p) { progress.setProgress(p); }

            @Override
            public void onShowCustomView(View view, CustomViewCallback cb) {   // full screen video / full screen test
                if (customView != null) { cb.onCustomViewHidden(); return; }
                customView = view;
                customCallback = cb;
                fullscreen.addView(view, new FrameLayout.LayoutParams(ViewGroup.LayoutParams.MATCH_PARENT, ViewGroup.LayoutParams.MATCH_PARENT));
                fullscreen.setVisibility(View.VISIBLE);
                setFullscreenBars(true);
            }

            @Override public void onHideCustomView() { exitFullscreen(); }

            @Override
            public void onGeolocationPermissionsShowPrompt(String origin, GeolocationPermissions.Callback cb) {
                cb.invoke(origin, false, false);                  // tests find the city from the internet address instead
            }

            @Override public void onPermissionRequest(PermissionRequest r) { r.deny(); }

            @Override
            public boolean onShowFileChooser(WebView view, ValueCallback<Uri[]> cb, FileChooserParams params) {
                if (fileCallback != null) fileCallback.onReceiveValue(null);
                fileCallback = cb;
                try {
                    startActivityForResult(params.createIntent(), REQ_FILE);
                } catch (ActivityNotFoundException e) {
                    fileCallback = null;
                    return false;
                }
                return true;
            }

            @Override
            public Bitmap getDefaultVideoPoster() {               // no grey "play" placeholder before a video starts
                return Bitmap.createBitmap(1, 1, Bitmap.Config.ARGB_8888);
            }
        });

        web.setDownloadListener(new DownloadListener() {
            @Override
            public void onDownloadStart(String url, String ua, String disposition, String mime, long length) {
                download(url, ua, disposition, mime);
            }
        });
    }

    /** Decides where a link opens. Returns true when the app handled it (the WebView must not load it). */
    private boolean route(Uri u) {
        String scheme = u.getScheme() == null ? "" : u.getScheme().toLowerCase();
        if (scheme.equals("http") || scheme.equals("https")) {
            if (Config.isOwnHost(u.getHost())) {
                String path = u.getPath() == null ? "/" : u.getPath();
                if (path.startsWith("/login")) {                 // Google does not allow sign-in inside an app's web view
                    openGoogleLogin(u.getQueryParameter("next"));
                    return true;
                }
                return false;
            }
            openInBrowser(u);
            return true;
        }
        if (scheme.equals("intent")) {
            try {
                Intent i = Intent.parseUri(u.toString(), Intent.URI_INTENT_SCHEME);
                i.addCategory(Intent.CATEGORY_BROWSABLE);
                i.setComponent(null);
                i.setSelector(null);
                try {
                    startActivity(i);
                } catch (ActivityNotFoundException e) {
                    String fallback = i.getStringExtra("browser_fallback_url");
                    if (fallback != null) route(Uri.parse(fallback));
                }
            } catch (Exception ignored) { }
            return true;
        }
        try {                                                     // tel:, mailto:, whatsapp:, market: …
            startActivity(new Intent(Intent.ACTION_VIEW, u));
        } catch (ActivityNotFoundException e) {
            toast(getString(R.string.no_app));
        }
        return true;
    }

    private void onPathChanged(Uri u) {
        String path = u.getPath() == null ? "" : u.getPath();
        boolean test = path.startsWith("/cbt/");
        pull.setPullEnabled(!test);                               // never reload a running test by a pull
        if (test) getWindow().addFlags(WindowManager.LayoutParams.FLAG_KEEP_SCREEN_ON);
        else getWindow().clearFlags(WindowManager.LayoutParams.FLAG_KEEP_SCREEN_ON);
    }

    /* ---------------- Google login: Chrome Custom Tab → website → back into the app ---------------- */

    private void openGoogleLogin(String next) {
        byte[] raw = new byte[32];
        new SecureRandom().nextBytes(raw);
        String verifier = b64(raw);
        String challenge;
        try {
            challenge = b64(MessageDigest.getInstance("SHA-256").digest(verifier.getBytes("US-ASCII")));
        } catch (Exception e) {
            return;
        }
        prefs.edit().putString("verifier", verifier).putLong("verifier_at", System.currentTimeMillis()).apply();
        Uri u = Uri.parse(Config.BASE_URL + "/login/").buildUpon()
                .appendQueryParameter("next", next != null && next.startsWith("/") ? next : "/student/")
                .appendQueryParameter("app", challenge).build();
        openCustomTab(u, true);
    }

    private static String b64(byte[] b) {
        return Base64.encodeToString(b, Base64.URL_SAFE | Base64.NO_PADDING | Base64.NO_WRAP);
    }

    /** The website sends the student back as itiexam://auth?code=…; the code is swapped for a login inside the app. */
    private boolean handleIntent(Intent intent) {
        Uri data = intent == null ? null : intent.getData();
        if (data == null) return false;
        if ("itiexam".equals(data.getScheme()) && "auth".equals(data.getHost())) {
            String code = data.getQueryParameter("code"), next = data.getQueryParameter("next");
            String verifier = prefs.getString("verifier", null);
            long at = prefs.getLong("verifier_at", 0);
            prefs.edit().remove("verifier").remove("verifier_at").apply();
            if (code == null || verifier == null || System.currentTimeMillis() - at > 30 * 60 * 1000L) {
                toast(getString(R.string.login_again));
                if (web.getUrl() == null) web.loadUrl(Config.BASE_URL + "/");
                return true;
            }
            web.loadUrl(Uri.parse(Config.BASE_URL + "/app-auth/claim/").buildUpon()
                    .appendQueryParameter("code", code).appendQueryParameter("v", verifier)
                    .appendQueryParameter("next", next != null && next.startsWith("/") ? next : "/student/").build().toString());
            return true;
        }
        if (("https".equals(data.getScheme()) || "http".equals(data.getScheme())) && Config.isOwnHost(data.getHost())) {
            web.loadUrl(data.toString().replaceFirst("^http:", "https:"));
            return true;
        }
        return false;
    }

    @Override
    protected void onNewIntent(Intent intent) {
        super.onNewIntent(intent);
        setIntent(intent);
        handleIntent(intent);
    }

    /* ---------------- links that leave the app ---------------- */

    private void openInBrowser(Uri u) {
        String host = u.getHost() == null ? "" : u.getHost();
        // Apps first (YouTube, WhatsApp, Telegram, Play Store …), else a tab in the browser
        if (host.contains("youtube.") || host.contains("youtu.be") || host.contains("whatsapp.") || host.equals("wa.me")
                || host.equals("t.me") || host.contains("play.google.") || host.contains("facebook.") || host.contains("instagram.")) {
            try { startActivity(new Intent(Intent.ACTION_VIEW, u)); return; } catch (ActivityNotFoundException ignored) { }
        }
        openCustomTab(u, false);
    }

    /** A Chrome Custom Tab in the brand colour (no extra library needed: it is plain intent extras). */
    private void openCustomTab(Uri u, boolean ownSite) {
        Intent i = new Intent(Intent.ACTION_VIEW, u);
        Bundle extras = new Bundle();
        extras.putBinder(CT_SESSION, null);
        i.putExtras(extras);
        i.putExtra("android.support.customtabs.extra.TOOLBAR_COLOR", Config.BRAND_NAVY);
        i.putExtra("android.support.customtabs.extra.TITLE_VISIBILITY", 1);
        i.putExtra("androidx.browser.customtabs.extra.SHARE_STATE", ownSite ? 2 : 0);
        // Our own pages would open straight back in this app (app links), so name the browser explicitly
        String browser = browserPackage();
        if (browser != null) i.setPackage(browser);
        try {
            startActivity(i);
        } catch (ActivityNotFoundException e) {
            i.setPackage(null);
            try { startActivity(i); } catch (ActivityNotFoundException e2) { toast(getString(R.string.no_browser)); }
        }
    }

    private String browserPackage() {
        PackageManager pm = getPackageManager();
        Intent probe = new Intent(Intent.ACTION_VIEW, Uri.parse("https://www.example.com/"));
        ResolveInfo def = pm.resolveActivity(probe, PackageManager.MATCH_DEFAULT_ONLY);
        String defPkg = def != null && def.activityInfo != null ? def.activityInfo.packageName : null;
        List<ResolveInfo> all = pm.queryIntentActivities(probe, 0);
        String chrome = null, any = null;
        for (ResolveInfo r : all) {
            String p = r.activityInfo.packageName;
            if (p.equals(getPackageName())) continue;
            if (p.equals(defPkg)) return p;
            if (p.equals("com.android.chrome")) chrome = p;
            if (any == null) any = p;
        }
        return chrome != null ? chrome : any;
    }

    /* ---------------- downloads (PDF notes, papers) ---------------- */

    private void download(String url, String ua, String disposition, String mime) {
        try {
            String name = URLUtil.guessFileName(url, disposition, mime);
            DownloadManager.Request r = new DownloadManager.Request(Uri.parse(url));
            r.setMimeType(mime);
            String cookies = CookieManager.getInstance().getCookie(url);
            if (cookies != null) r.addRequestHeader("Cookie", cookies);
            r.addRequestHeader("User-Agent", ua);
            r.setTitle(name);
            r.setDescription(getString(R.string.app_name));
            r.setNotificationVisibility(DownloadManager.Request.VISIBILITY_VISIBLE_NOTIFY_COMPLETED);
            if (Build.VERSION.SDK_INT >= 29) r.setDestinationInExternalPublicDir(Environment.DIRECTORY_DOWNLOADS, name);
            else r.setDestinationInExternalFilesDir(this, Environment.DIRECTORY_DOWNLOADS, name);   // needs no storage permission
            ((DownloadManager) getSystemService(Context.DOWNLOAD_SERVICE)).enqueue(r);
            toast(getString(R.string.downloading, name));
        } catch (Exception e) {
            openCustomTab(Uri.parse(url), false);
        }
    }

    @Override
    protected void onActivityResult(int req, int result, Intent data) {
        if (req == REQ_FILE && fileCallback != null) {
            fileCallback.onReceiveValue(WebChromeClient.FileChooserParams.parseResult(result, data));
            fileCallback = null;
            return;
        }
        super.onActivityResult(req, result, data);
    }

    /* ---------------- splash, offline, full screen ---------------- */

    private void setUpSplash(boolean restoring) {
        if (restoring) { splash.setVisibility(View.GONE); splashGone = true; return; }
        splashShownAt = System.currentTimeMillis();
        View logo = findViewById(R.id.splash_logo), texts = findViewById(R.id.splash_texts);
        logo.setScaleX(0.6f); logo.setScaleY(0.6f); logo.setAlpha(0f);
        logo.animate().scaleX(1f).scaleY(1f).alpha(1f).setDuration(650).setInterpolator(new android.view.animation.OvershootInterpolator(1.6f)).start();
        texts.setTranslationY(24 * getResources().getDisplayMetrics().density); texts.setAlpha(0f);
        texts.animate().translationY(0).alpha(1f).setStartDelay(250).setDuration(500).setInterpolator(new DecelerateInterpolator()).start();
        ui.postDelayed(new Runnable() { @Override public void run() { hideSplash(); } }, 9000);   // never wait for ever
    }

    private void hideSplash() {
        if (splashGone) return;
        long wait = Math.max(0, 1100 - (System.currentTimeMillis() - splashShownAt));   // let the intro play
        splashGone = true;
        ui.postDelayed(new Runnable() {
            @Override public void run() {
                splash.animate().alpha(0f).scaleX(1.04f).scaleY(1.04f).setDuration(320).withEndAction(new Runnable() {
                    @Override public void run() { splash.setVisibility(View.GONE); }
                }).start();
            }
        }, wait);
    }

    private void showOffline() {
        hideSplash();
        if (offline.getVisibility() == View.VISIBLE) return;
        offline.setAlpha(0f);
        offline.setVisibility(View.VISIBLE);
        offline.animate().alpha(1f).setDuration(220).start();
    }

    private void retry() {
        if (web.getUrl() == null || web.getUrl().startsWith("data:") || web.getUrl().equals("about:blank")) web.loadUrl(Config.BASE_URL + "/");
        else web.reload();
    }

    private void watchNetwork() {
        ConnectivityManager cm = (ConnectivityManager) getSystemService(Context.CONNECTIVITY_SERVICE);
        if (cm == null) return;
        netCallback = new ConnectivityManager.NetworkCallback() {
            @Override public void onAvailable(Network n) {
                ui.post(new Runnable() {
                    @Override public void run() { if (offline.getVisibility() == View.VISIBLE) retry(); }   // back online: try again by itself
                });
            }
        };
        try { cm.registerDefaultNetworkCallback(netCallback); } catch (Exception ignored) { netCallback = null; }
    }

    private void exitFullscreen() {
        if (customView == null) return;
        fullscreen.removeView(customView);
        fullscreen.setVisibility(View.GONE);
        customView = null;
        if (customCallback != null) customCallback.onCustomViewHidden();
        customCallback = null;
        setFullscreenBars(false);
    }

    private void setFullscreenBars(boolean on) {
        if (Build.VERSION.SDK_INT >= 30) {
            WindowInsetsController c = getWindow().getInsetsController();
            if (c == null) return;
            if (on) {
                c.hide(WindowInsets.Type.systemBars());
                c.setSystemBarsBehavior(WindowInsetsController.BEHAVIOR_SHOW_TRANSIENT_BARS_BY_SWIPE);
            } else c.show(WindowInsets.Type.systemBars());
        } else {
            View d = getWindow().getDecorView();
            int base = View.SYSTEM_UI_FLAG_LAYOUT_STABLE | View.SYSTEM_UI_FLAG_LAYOUT_FULLSCREEN | View.SYSTEM_UI_FLAG_LIGHT_STATUS_BAR;
            if (Build.VERSION.SDK_INT >= 27) base |= View.SYSTEM_UI_FLAG_LAYOUT_HIDE_NAVIGATION | View.SYSTEM_UI_FLAG_LIGHT_NAVIGATION_BAR;
            d.setSystemUiVisibility(on ? base | View.SYSTEM_UI_FLAG_FULLSCREEN | View.SYSTEM_UI_FLAG_HIDE_NAVIGATION | View.SYSTEM_UI_FLAG_IMMERSIVE_STICKY : base);
        }
    }

    /* ---------------- back button ---------------- */

    private void setUpBack() {
        if (Build.VERSION.SDK_INT >= 33) {
            getOnBackInvokedDispatcher().registerOnBackInvokedCallback(OnBackInvokedDispatcher.PRIORITY_DEFAULT, new OnBackInvokedCallback() {
                @Override public void onBackInvoked() { goBack(); }
            });
        }
    }

    @Override
    @SuppressWarnings("deprecation")
    public void onBackPressed() { goBack(); }

    private void goBack() {
        if (customView != null) { exitFullscreen(); return; }
        if (web.canGoBack() && offline.getVisibility() != View.VISIBLE) { web.goBack(); return; }
        if (offline.getVisibility() == View.VISIBLE && web.canGoBack()) { offline.setVisibility(View.GONE); web.goBack(); return; }
        long now = System.currentTimeMillis();
        if (now - lastBack < 2000) { finish(); return; }
        lastBack = now;
        toast(getString(R.string.press_again));
    }

    /* ---------------- lifecycle ---------------- */

    @Override protected void onSaveInstanceState(Bundle out) { super.onSaveInstanceState(out); web.saveState(out); }

    @Override protected void onResume() { super.onResume(); web.onResume(); }

    @Override protected void onPause() { CookieManager.getInstance().flush(); web.onPause(); super.onPause(); }

    @Override
    protected void onDestroy() {
        ui.removeCallbacksAndMessages(null);
        if (netCallback != null) {
            try { ((ConnectivityManager) getSystemService(Context.CONNECTIVITY_SERVICE)).unregisterNetworkCallback(netCallback); } catch (Exception ignored) { }
        }
        if (web != null) { ((ViewGroup) web.getParent()).removeView(web); web.destroy(); }
        super.onDestroy();
    }

    private void toast(String s) { Toast.makeText(this, s, Toast.LENGTH_SHORT).show(); }
}
