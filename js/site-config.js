/* =========================================================
   COPA MCQ – Site configuration
   ---------------------------------------------------------
   1) FIREBASE (online database for the Admin Panel)
      Without Firebase the site works in "local mode": data is
      saved only in the browser of each computer, so the admin
      sees only that computer's data.
      To see ALL students live:
        a. Go to https://console.firebase.google.com → Add project
        b. Build → Firestore Database → Create database
        c. Build → Authentication → Sign-in method → Email/Password → Enable,
           then Users → Add user (this is your admin email + password)
        d. Project settings → Your apps → Web app (</>) → copy the config
           values into `firebase` below
        e. Firestore → Rules → paste the contents of firestore.rules
           (put your admin email in it) → Publish
      Put your admin email(s) in `adminEmails` too.

   2) GOOGLE ADSENSE
      These are the default ad settings. With Firebase, the Admin
      Panel → "Ads & Settings" saves them online and they override
      this file. Without Firebase, use the Admin Panel's
      "Download site-config.js" button and upload the new file.
   ========================================================= */
window.COPA_CONFIG = {
  firebase: null,
  /* example:
  firebase: {
    apiKey: "AIza...",
    authDomain: "your-project.firebaseapp.com",
    projectId: "your-project",
    storageBucket: "your-project.appspot.com",
    messagingSenderId: "123456789",
    appId: "1:123456789:web:abc123"
  },
  */
  adminEmails: [],

  siteUrl: "https://copamcq.com",

  adsense: {
    enabled: false,
    client: "",          // ca-pub-XXXXXXXXXXXXXXXX
    autoAds: true,       // let Google place ads automatically
    slots: {             // optional manual ad-unit slot IDs
      top: "",
      middle: "",
      bottom: ""
    }
  }
};
