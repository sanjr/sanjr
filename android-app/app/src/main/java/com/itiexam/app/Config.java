package com.itiexam.app;

/** Website the app shows. Change BASE_URL here when the site moves to its final domain. */
final class Config {
    static final String BASE_URL = "https://itiexam.com";

    /** Pages on these hosts open inside the app; everything else opens in the browser or the matching app. */
    static final String[] HOSTS = {"blog.copamcq.com", "copamcq.com", "www.copamcq.com", "itiexam.com", "www.itiexam.com"};

    static final String USER_AGENT_TAG = "ITIExamApp/" + BuildInfo.VERSION_NAME;
    static final int BRAND_NAVY = 0xFF0B2A6F;

    static boolean isOwnHost(String host) {
        if (host == null) return false;
        for (String h : HOSTS) if (h.equalsIgnoreCase(host)) return true;
        return false;
    }

    private Config() {}
}
