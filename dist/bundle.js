;{try{let e="undefined"!=typeof window?window:"undefined"!=typeof global?global:"undefined"!=typeof globalThis?globalThis:"undefined"!=typeof self?self:{},n=(new e.Error).stack;n&&(e._sentryDebugIds=e._sentryDebugIds||{},e._sentryDebugIds[n]="06d791d7-7a6e-4e3e-a77d-6c45f1229b5a",e._sentryDebugIdIdentifier="sentry-dbid-06d791d7-7a6e-4e3e-a77d-6c45f1229b5a")}catch(e){}};
;{try{let e="undefined"!=typeof window?window:"undefined"!=typeof global?global:"undefined"!=typeof globalThis?globalThis:"undefined"!=typeof self?self:{},n=(new e.Error).stack;n&&(e._sentryDebugIds=e._sentryDebugIds||{},e._sentryDebugIds[n]="06d791d7-7a6e-4e3e-a77d-6c45f1229b5a",e._sentryDebugIdIdentifier="sentry-dbid-06d791d7-7a6e-4e3e-a77d-6c45f1229b5a")}catch(e){}};
/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	// The require scope
/******/ 	var __webpack_require__ = {};
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
/*!*************************!*\
  !*** ./src/js/index.js ***!
  \*************************/
__webpack_require__.r(__webpack_exports__);
window.onload = function () {
  // Debugging isLoggedIn variable
  console.log("isLoggedIn:", typeof isLoggedIn !== "undefined" ? isLoggedIn : "Not defined");
  if (typeof isLoggedIn === "undefined") {
    console.error("isLoggedIn is not defined");
    return;
  }

  // Debugging isAdmin variable
  console.log("isAdmin:", typeof isAdmin !== "undefined" ? isAdmin : "Not defined");
  if (typeof isAdmin === "undefined") {
    console.error("isAdmin is not defined");
    return;
  }

  // Debugging reCAPTCHA variable
  console.log("recaptchaVerified:", typeof recaptchaVerified !== "undefined" ? recaptchaVerified : "Not defined");
  if (typeof recaptchaVerified === "undefined") {
    console.error("recaptchaVerified is not defined");
    return;
  }

  // Debugging Session variable
  console.log("Session:", typeof Session !== "undefined" ? Session : "Not defined");
  if (typeof Session === "undefined") {
    console.error("Session is not defined");
    return;
  }

  // Debugging User_id variable
  console.log("User_id:", typeof User_id !== "undefined" ? User_id : "Not defined");
  if (typeof User_id === "undefined") {
    console.error("User_id is not defined");
    return;
  }
  var fadeElements = document.querySelectorAll('.fade-in');
  fadeElements.forEach(function (element) {
    element.classList.add('show');
  });

  // Get button and badge elements
  var loginButton = document.getElementById('loginButton');
  var libraryButton = document.getElementById('libraryButton');
  var libraryBadge = document.getElementById('library_badge');
  var cartButton = document.getElementById('cartButton');
  var cartBadge = document.getElementById('cart_badge');
  var sessionButton = document.getElementById('sessionButton');
  var adminButton = document.getElementById('adminButton');

  // Update UI based on login state
  if (isLoggedIn) {
    loginButton.innerHTML = "My&nbsp;&nbsp;Account";
    loginButton.href = "account.php";
    libraryButton.classList.remove("disabled");
    libraryButton.removeAttribute("aria-disabled");
    //libraryBadge?.removeAttribute('hidden'); 
    cartButton.classList.remove("disabled");
    cartButton.removeAttribute("aria-disabled");
    //cartBadge?.removeAttribute('hidden');
    sessionButton === null || sessionButton === void 0 || sessionButton.removeAttribute('hidden');
  } else {
    loginButton.innerHTML = "Login";
    loginButton.href = "login.php";
    libraryButton.classList.add("disabled");
    libraryButton.setAttribute("aria-disabled", "true");
    libraryBadge === null || libraryBadge === void 0 || libraryBadge.setAttribute('hidden', 'hidden');
    cartButton.classList.add("disabled");
    cartButton.setAttribute("aria-disabled", "true");
    cartBadge === null || cartBadge === void 0 || cartBadge.setAttribute('hidden', 'hidden');
    sessionButton === null || sessionButton === void 0 || sessionButton.setAttribute('hidden', 'hidden');
  }
  if (isAdmin) {
    adminButton === null || adminButton === void 0 || adminButton.removeAttribute('hidden');
  }
};

//Update cart badge on header Function
window.updateCartBadge = function () {
  var cartItems = JSON.parse(localStorage.getItem('cartItems')) || []; // Retrieve cart items from localStorage
  var cartCount = cartItems.length || 0; // Get the number of items in the cart

  // Find the badge element in the Cart button
  var cartBadge = document.querySelector('#cartButton .badge');
  if (cartBadge) {
    cartBadge.textContent = cartCount; // Update badge count
    cartBadge.hidden = cartCount === 0; // Hide badge if count is 0
  }
};
// Update library badge on header Function
window.updateLibraryBadge = function () {
  var libraryCount = JSON.parse(localStorage.getItem('libraryCount')) || 0; // Get count from localStorage
  var libraryBadge = document.querySelector('#libraryButton .badge');
  // Find the badge element in the Library button
  if (libraryBadge) {
    libraryBadge.textContent = libraryCount; // Update badge count
    libraryBadge.hidden = libraryCount === 0; // Hide badge if count is 0
  }
};

// Show/Hide Password Function
document.addEventListener("DOMContentLoaded", function () {
  // Select all password fields that start with "password" [id^='password']
  document.querySelectorAll("input[type='password']").forEach(function (passwordInput) {
    var id = passwordInput.id; // Get the ID of the password input
    var parent = passwordInput.parentElement; // Get the parent container
    if (parent.querySelector("#toggle".concat(id))) return;

    // Create the toggle icon dynamically
    var toggleIcon = document.createElement("i");
    toggleIcon.classList.add("bi", "bi-eye-slash", "position-absolute", "top-50", "end-0", "translate-middle-y", "me-3");
    toggleIcon.style.cursor = "pointer";
    toggleIcon.id = "toggle".concat(id); // Assign a unique ID

    // Ensure the parent has relative positioning
    parent.classList.add("position-relative");
    // Append the icon inside the parent container
    parent.appendChild(toggleIcon);

    // Add event listener for toggling password visibility
    toggleIcon.addEventListener("click", function () {
      if (passwordInput.type === "password") {
        passwordInput.type = "text"; // Show password
        toggleIcon.classList.replace("bi-eye-slash", "bi-eye-fill");
      } else {
        passwordInput.type = "password"; // Hide password
        toggleIcon.classList.replace("bi-eye-fill", "bi-eye-slash");
      }
    });
  });
});

// Convert timestamp or Date to 'YYYY-MM-DD HH:mm:ss' in Hong Kong time
function formatHKDateTime(input) {
  var date = input instanceof Date ? input : new Date(input);

  // Force Hong Kong timezone conversion using Intl
  var options = {
    timeZone: 'Asia/Hong_Kong',
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    hour12: false
  };
  var formatter = new Intl.DateTimeFormat('en-GB', options);
  var parts = formatter.formatToParts(date).reduce(function (acc, part) {
    acc[part.type] = part.value;
    return acc;
  }, {});
  return "".concat(parts.year, "-").concat(parts.month, "-").concat(parts.day, " ").concat(parts.hour, ":").concat(parts.minute, ":").concat(parts.second);
}

// Call the function initially to ensure the badge is updated on page load
updateCartBadge();
updateLibraryBadge();
// Save the current page URL before redirecting to the login page
if (!localStorage.getItem("preLoginUrl") && window.location.pathname.split("/").pop() !== "login.php") localStorage.setItem("preLoginUrl", window.location.href);

// Sentry Debug
Sentry.onLoad(function () {
  Sentry.init({
    // Tracing
    tracesSampleRate: 1.0,
    // Capture 100% of the transactions
    // Session Replay
    replaysSessionSampleRate: 0.5,
    // This sets the sample rate at 50%. You may want to change it to 100% while in development and then sample at a lower rate in production.
    replaysOnErrorSampleRate: 1.0 // If you're not already sampling the entire session, change the sample rate to 100% when sampling sessions where errors occur.
  });
});
Sentry.replayIntegration({
  unblock: [".sentry-unblock, [data-sentry-unblock]"],
  unmask: [".sentry-unmask, [data-sentry-unmask]"]
});
//myUndefinedFunction();
/******/ })()
;
//# sourceMappingURL=bundle.js.map