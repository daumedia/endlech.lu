(self["webpackChunk"] = self["webpackChunk"] || []).push([["app"],{

/***/ "./assets/app.ts"
/*!***********************!*\
  !*** ./assets/app.ts ***!
  \***********************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _stimulus_bootstrap__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./stimulus_bootstrap */ "./assets/stimulus_bootstrap.ts");
/* harmony import */ var _usage_before_send__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./usage/before_send */ "./assets/usage/before_send.ts");
/* harmony import */ var _styles_app_css__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./styles/app.css */ "./assets/styles/app.css");
/* harmony import */ var tom_select_dist_css_tom_select_css__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! tom-select/dist/css/tom-select.css */ "./node_modules/tom-select/dist/css/tom-select.css");
/* harmony import */ var glightbox__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! glightbox */ "./node_modules/glightbox/dist/js/glightbox.min.js");
/* harmony import */ var glightbox__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(glightbox__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var glightbox_dist_css_glightbox_css__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! glightbox/dist/css/glightbox.css */ "./node_modules/glightbox/dist/css/glightbox.css");

// Nutzungsmessung (Feature 11): Vor-Versand-Prüfung, die das Zählskript über data-before-send ruft.

/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */
// any CSS you import will output into a single css file (app.css in this case)

// Tom Select CSS für Autocomplete-Selects

// GLightbox – Lightbox für Restaurant-Fotos


document.addEventListener('DOMContentLoaded', function () {
  glightbox__WEBPACK_IMPORTED_MODULE_4___default()({
    selector: '.glightbox'
  });
});
// PWA: Service Worker registrieren (Offline-Support, installierbar – Issue #83)
if ('serviceWorker' in navigator) {
  window.addEventListener('load', function () {
    navigator.serviceWorker.register('/sw.js', {
      scope: '/'
    })["catch"](function () {
      // Registrierung fehlgeschlagen – App funktioniert ohne SW weiter.
    });
  });
}

/***/ },

/***/ "./assets/controllers sync recursive ./node_modules/@symfony/stimulus-bridge/lazy-controller-loader.js! \\.[jt]sx?$"
/*!****************************************************************************************************************!*\
  !*** ./assets/controllers/ sync ./node_modules/@symfony/stimulus-bridge/lazy-controller-loader.js! \.[jt]sx?$ ***!
  \****************************************************************************************************************/
(module, __unused_webpack_exports, __webpack_require__) {

var map = {
	"./collection_form_controller.ts": "./node_modules/@symfony/stimulus-bridge/lazy-controller-loader.js!./assets/controllers/collection_form_controller.ts",
	"./cookie_consent_controller.ts": "./node_modules/@symfony/stimulus-bridge/lazy-controller-loader.js!./assets/controllers/cookie_consent_controller.ts",
	"./csrf_protection_controller.ts": "./node_modules/@symfony/stimulus-bridge/lazy-controller-loader.js!./assets/controllers/csrf_protection_controller.ts",
	"./hello_controller.ts": "./node_modules/@symfony/stimulus-bridge/lazy-controller-loader.js!./assets/controllers/hello_controller.ts",
	"./image_sort_controller.ts": "./node_modules/@symfony/stimulus-bridge/lazy-controller-loader.js!./assets/controllers/image_sort_controller.ts",
	"./language_switcher_controller.ts": "./node_modules/@symfony/stimulus-bridge/lazy-controller-loader.js!./assets/controllers/language_switcher_controller.ts",
	"./nav_dropdown_controller.ts": "./node_modules/@symfony/stimulus-bridge/lazy-controller-loader.js!./assets/controllers/nav_dropdown_controller.ts",
	"./opening_hours_form_controller.ts": "./node_modules/@symfony/stimulus-bridge/lazy-controller-loader.js!./assets/controllers/opening_hours_form_controller.ts",
	"./organisation_type_controller.ts": "./node_modules/@symfony/stimulus-bridge/lazy-controller-loader.js!./assets/controllers/organisation_type_controller.ts",
	"./passkey_ui_controller.ts": "./node_modules/@symfony/stimulus-bridge/lazy-controller-loader.js!./assets/controllers/passkey_ui_controller.ts",
	"./suggestion_wizard_controller.ts": "./node_modules/@symfony/stimulus-bridge/lazy-controller-loader.js!./assets/controllers/suggestion_wizard_controller.ts",
	"./tom_select_controller.ts": "./node_modules/@symfony/stimulus-bridge/lazy-controller-loader.js!./assets/controllers/tom_select_controller.ts",
	"./usage_event_controller.ts": "./node_modules/@symfony/stimulus-bridge/lazy-controller-loader.js!./assets/controllers/usage_event_controller.ts",
	"./usage_opt_out_controller.ts": "./node_modules/@symfony/stimulus-bridge/lazy-controller-loader.js!./assets/controllers/usage_opt_out_controller.ts"
};


function webpackContext(req) {
	var id = webpackContextResolve(req);
	return __webpack_require__(id);
}
function webpackContextResolve(req) {
	if(!__webpack_require__.o(map, req)) {
		var e = new Error("Cannot find module '" + req + "'");
		e.code = 'MODULE_NOT_FOUND';
		throw e;
	}
	return map[req];
}
webpackContext.keys = function webpackContextKeys() {
	return Object.keys(map);
};
webpackContext.resolve = webpackContextResolve;
module.exports = webpackContext;
webpackContext.id = "./assets/controllers sync recursive ./node_modules/@symfony/stimulus-bridge/lazy-controller-loader.js! \\.[jt]sx?$";

/***/ },

/***/ "./assets/stimulus_bootstrap.ts"
/*!**************************************!*\
  !*** ./assets/stimulus_bootstrap.ts ***!
  \**************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   app: () => (/* binding */ app)
/* harmony export */ });
/* harmony import */ var _symfony_stimulus_bridge__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @symfony/stimulus-bridge */ "./node_modules/@symfony/stimulus-bridge/dist/index.js");
/* harmony import */ var _web_auth_webauthn_stimulus__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @web-auth/webauthn-stimulus */ "./node_modules/@web-auth/webauthn-stimulus/src/index.js");


// Registers Stimulus controllers from controllers.json and in the controllers/ directory
var app = (0,_symfony_stimulus_bridge__WEBPACK_IMPORTED_MODULE_0__.startStimulusApp)(__webpack_require__("./assets/controllers sync recursive ./node_modules/@symfony/stimulus-bridge/lazy-controller-loader.js! \\.[jt]sx?$"));
// register any custom, 3rd party controllers here
// Passkeys: Die beiden Controller des WebAuthn-Bundles bringen den
// WebAuthn-Ablauf samt base64url-Kodierung und Fehlerklassen mit.
//
// Bewusst hier und NICHT in controllers.json: Das StimulusBundle löst jeden
// Eintrag dort gegen ein gleichnamiges Composer-Paket auf – das Paket lebt aber
// nur auf npm, der Container-Build bräche mit "Could not find package".
//
// Eigene, kurze Bezeichner statt der langen Vorgabe aus der Bundle-Doku: Die
// Templates schreiben die data-Attribute ohnehin von Hand, und
// `data-passkey-auth-…` liest sich besser als
// `data-web-auth--webauthn-stimulus--authentication-…`.
app.register('passkey-auth', _web_auth_webauthn_stimulus__WEBPACK_IMPORTED_MODULE_1__.AuthenticationController);
app.register('passkey-register', _web_auth_webauthn_stimulus__WEBPACK_IMPORTED_MODULE_1__.RegistrationController);

/***/ },

/***/ "./assets/styles/app.css"
/*!*******************************!*\
  !*** ./assets/styles/app.css ***!
  \*******************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ },

/***/ "./assets/usage/before_send.ts"
/*!*************************************!*\
  !*** ./assets/usage/before_send.ts ***!
  \*************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   vorVersand: () => (/* binding */ vorVersand)
/* harmony export */ });
/* harmony import */ var core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! core-js/modules/es.array.iterator.js */ "./node_modules/core-js/modules/es.array.iterator.js");
/* harmony import */ var core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! core-js/modules/es.object.to-string.js */ "./node_modules/core-js/modules/es.object.to-string.js");
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! core-js/modules/es.regexp.exec.js */ "./node_modules/core-js/modules/es.regexp.exec.js");
/* harmony import */ var core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var core_js_modules_es_regexp_test_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! core-js/modules/es.regexp.test.js */ "./node_modules/core-js/modules/es.regexp.test.js");
/* harmony import */ var core_js_modules_es_regexp_test_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_regexp_test_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! core-js/modules/es.string.iterator.js */ "./node_modules/core-js/modules/es.string.iterator.js");
/* harmony import */ var core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! core-js/modules/web.dom-collections.iterator.js */ "./node_modules/core-js/modules/web.dom-collections.iterator.js");
/* harmony import */ var core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var core_js_modules_web_url_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! core-js/modules/web.url.js */ "./node_modules/core-js/modules/web.url.js");
/* harmony import */ var core_js_modules_web_url_js__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_url_js__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var core_js_modules_web_url_to_json_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! core-js/modules/web.url.to-json.js */ "./node_modules/core-js/modules/web.url.to-json.js");
/* harmony import */ var core_js_modules_web_url_to_json_js__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_url_to_json_js__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var core_js_modules_web_url_search_params_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! core-js/modules/web.url-search-params.js */ "./node_modules/core-js/modules/web.url-search-params.js");
/* harmony import */ var core_js_modules_web_url_search_params_js__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_url_search_params_js__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var core_js_modules_web_url_search_params_delete_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! core-js/modules/web.url-search-params.delete.js */ "./node_modules/core-js/modules/web.url-search-params.delete.js");
/* harmony import */ var core_js_modules_web_url_search_params_delete_js__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_url_search_params_delete_js__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var core_js_modules_web_url_search_params_has_js__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! core-js/modules/web.url-search-params.has.js */ "./node_modules/core-js/modules/web.url-search-params.has.js");
/* harmony import */ var core_js_modules_web_url_search_params_has_js__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_url_search_params_has_js__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var core_js_modules_web_url_search_params_size_js__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! core-js/modules/web.url-search-params.size.js */ "./node_modules/core-js/modules/web.url-search-params.size.js");
/* harmony import */ var core_js_modules_web_url_search_params_size_js__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_url_search_params_size_js__WEBPACK_IMPORTED_MODULE_11__);












/**
 * Vor-Versand-Prüfung des Zählskripts (Feature 11).
 *
 * Umami ruft diese Funktion vor **jedem** Zählaufruf auf (`data-before-send` am Skript in
 * base.html.twig). Gibt sie `null` zurück, verlässt nichts den Browser.
 *
 * ⚠ **Global Privacy Control** kennt der Tracker nicht, nur „Do Not Track" — deshalb hier (AK-22).
 *
 * ⚠ **Die Pfadregeln stehen hier ein zweites Mal, und das ist Absicht.** Der Seitenkopf lässt das
 * Skript auf Verwaltung, Profil und Token-Seiten weg. Turbo Drive tauscht beim Navigieren aber nur
 * den Seiteninhalt: Ein einmal geladener Tracker bleibt aktiv und zählt 300 ms nach jedem
 * `pushState` — auch auf dem Weg nach `/de/admin`. Turbo ruft `pushState` schon zu Beginn eines
 * Seitenwechsels, eine Markierung im neuen Seiteninhalt käme also womöglich zu spät. Die Adresse
 * steht dagegen im Zählaufruf selbst. Dieselben Regeln prüft die Weiterleitung auf dem Server
 * (`CollectPayloadNormalizer`) — dort als Grenze, hier, damit gar nichts erst abgeht (AK-06, AK-07).
 */
var AUSGENOMMENE_PFADE = /^\/[a-z]{2}\/(admin|profile)(\/|$)/;
var TOKEN = /[a-f0-9]{64}/i;
function vorVersand(_typ, nutzlast) {
  if (!nutzlast) {
    return null;
  }
  if (navigator.globalPrivacyControl === true) {
    return null;
  }
  var pfad = pfadAus(nutzlast.url);
  if (null === pfad || AUSGENOMMENE_PFADE.test(pfad) || TOKEN.test(pfad)) {
    return null;
  }
  return nutzlast;
}
function pfadAus(adresse) {
  try {
    return new URL(adresse !== null && adresse !== void 0 ? adresse : '', window.location.href).pathname;
  } catch (_unused) {
    return null;
  }
}
window.endlechNutzungVorVersand = vorVersand;

/***/ },

/***/ "./node_modules/@symfony/stimulus-bridge/dist/webpack/loader.js!./assets/controllers.json"
/*!************************************************************************************************!*\
  !*** ./node_modules/@symfony/stimulus-bridge/dist/webpack/loader.js!./assets/controllers.json ***!
  \************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _symfony_ux_turbo_dist_turbo_controller_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @symfony/ux-turbo/dist/turbo_controller.js */ "./vendor/symfony/ux-turbo/assets/dist/turbo_controller.js");

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  'symfony--ux-turbo--turbo-core': _symfony_ux_turbo_dist_turbo_controller_js__WEBPACK_IMPORTED_MODULE_0__["default"],
});

/***/ },

/***/ "./node_modules/@symfony/stimulus-bridge/lazy-controller-loader.js!./assets/controllers/collection_form_controller.ts"
/*!****************************************************************************************************************************!*\
  !*** ./node_modules/@symfony/stimulus-bridge/lazy-controller-loader.js!./assets/controllers/collection_form_controller.ts ***!
  \****************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! core-js/modules/es.symbol.js */ "./node_modules/core-js/modules/es.symbol.js");
/* harmony import */ var core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! core-js/modules/es.symbol.description.js */ "./node_modules/core-js/modules/es.symbol.description.js");
/* harmony import */ var core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! core-js/modules/es.symbol.iterator.js */ "./node_modules/core-js/modules/es.symbol.iterator.js");
/* harmony import */ var core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var core_js_modules_es_symbol_to_primitive_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! core-js/modules/es.symbol.to-primitive.js */ "./node_modules/core-js/modules/es.symbol.to-primitive.js");
/* harmony import */ var core_js_modules_es_symbol_to_primitive_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_to_primitive_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var core_js_modules_es_error_cause_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! core-js/modules/es.error.cause.js */ "./node_modules/core-js/modules/es.error.cause.js");
/* harmony import */ var core_js_modules_es_error_cause_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_error_cause_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var core_js_modules_es_error_to_string_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! core-js/modules/es.error.to-string.js */ "./node_modules/core-js/modules/es.error.to-string.js");
/* harmony import */ var core_js_modules_es_error_to_string_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_error_to_string_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! core-js/modules/es.array.iterator.js */ "./node_modules/core-js/modules/es.array.iterator.js");
/* harmony import */ var core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var core_js_modules_es_date_to_primitive_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! core-js/modules/es.date.to-primitive.js */ "./node_modules/core-js/modules/es.date.to-primitive.js");
/* harmony import */ var core_js_modules_es_date_to_primitive_js__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_date_to_primitive_js__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var core_js_modules_es_function_bind_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! core-js/modules/es.function.bind.js */ "./node_modules/core-js/modules/es.function.bind.js");
/* harmony import */ var core_js_modules_es_function_bind_js__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_function_bind_js__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var core_js_modules_es_number_constructor_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! core-js/modules/es.number.constructor.js */ "./node_modules/core-js/modules/es.number.constructor.js");
/* harmony import */ var core_js_modules_es_number_constructor_js__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_number_constructor_js__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var core_js_modules_es_object_create_js__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! core-js/modules/es.object.create.js */ "./node_modules/core-js/modules/es.object.create.js");
/* harmony import */ var core_js_modules_es_object_create_js__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_create_js__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var core_js_modules_es_object_define_property_js__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! core-js/modules/es.object.define-property.js */ "./node_modules/core-js/modules/es.object.define-property.js");
/* harmony import */ var core_js_modules_es_object_define_property_js__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_define_property_js__WEBPACK_IMPORTED_MODULE_11__);
/* harmony import */ var core_js_modules_es_object_get_prototype_of_js__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! core-js/modules/es.object.get-prototype-of.js */ "./node_modules/core-js/modules/es.object.get-prototype-of.js");
/* harmony import */ var core_js_modules_es_object_get_prototype_of_js__WEBPACK_IMPORTED_MODULE_12___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_get_prototype_of_js__WEBPACK_IMPORTED_MODULE_12__);
/* harmony import */ var core_js_modules_es_object_proto_js__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! core-js/modules/es.object.proto.js */ "./node_modules/core-js/modules/es.object.proto.js");
/* harmony import */ var core_js_modules_es_object_proto_js__WEBPACK_IMPORTED_MODULE_13___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_proto_js__WEBPACK_IMPORTED_MODULE_13__);
/* harmony import */ var core_js_modules_es_object_set_prototype_of_js__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! core-js/modules/es.object.set-prototype-of.js */ "./node_modules/core-js/modules/es.object.set-prototype-of.js");
/* harmony import */ var core_js_modules_es_object_set_prototype_of_js__WEBPACK_IMPORTED_MODULE_14___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_set_prototype_of_js__WEBPACK_IMPORTED_MODULE_14__);
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! core-js/modules/es.object.to-string.js */ "./node_modules/core-js/modules/es.object.to-string.js");
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_15___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_15__);
/* harmony import */ var core_js_modules_es_reflect_construct_js__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! core-js/modules/es.reflect.construct.js */ "./node_modules/core-js/modules/es.reflect.construct.js");
/* harmony import */ var core_js_modules_es_reflect_construct_js__WEBPACK_IMPORTED_MODULE_16___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_reflect_construct_js__WEBPACK_IMPORTED_MODULE_16__);
/* harmony import */ var core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! core-js/modules/es.regexp.exec.js */ "./node_modules/core-js/modules/es.regexp.exec.js");
/* harmony import */ var core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_17___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_17__);
/* harmony import */ var core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! core-js/modules/es.string.iterator.js */ "./node_modules/core-js/modules/es.string.iterator.js");
/* harmony import */ var core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_18___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_18__);
/* harmony import */ var core_js_modules_es_string_replace_js__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__(/*! core-js/modules/es.string.replace.js */ "./node_modules/core-js/modules/es.string.replace.js");
/* harmony import */ var core_js_modules_es_string_replace_js__WEBPACK_IMPORTED_MODULE_19___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_string_replace_js__WEBPACK_IMPORTED_MODULE_19__);
/* harmony import */ var core_js_modules_es_weak_map_js__WEBPACK_IMPORTED_MODULE_20__ = __webpack_require__(/*! core-js/modules/es.weak-map.js */ "./node_modules/core-js/modules/es.weak-map.js");
/* harmony import */ var core_js_modules_es_weak_map_js__WEBPACK_IMPORTED_MODULE_20___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_weak_map_js__WEBPACK_IMPORTED_MODULE_20__);
/* harmony import */ var core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_21__ = __webpack_require__(/*! core-js/modules/web.dom-collections.iterator.js */ "./node_modules/core-js/modules/web.dom-collections.iterator.js");
/* harmony import */ var core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_21___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_21__);
/* harmony import */ var _hotwired_stimulus__WEBPACK_IMPORTED_MODULE_22__ = __webpack_require__(/*! @hotwired/stimulus */ "./node_modules/@hotwired/stimulus/dist/stimulus.js");
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _classCallCheck(a, n) { if (!(a instanceof n)) throw new TypeError("Cannot call a class as a function"); }
function _defineProperties(e, r) { for (var t = 0; t < r.length; t++) { var o = r[t]; o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(e, _toPropertyKey(o.key), o); } }
function _createClass(e, r, t) { return r && _defineProperties(e.prototype, r), t && _defineProperties(e, t), Object.defineProperty(e, "prototype", { writable: !1 }), e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != _typeof(t) || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }
function _callSuper(t, o, e) { return o = _getPrototypeOf(o), _possibleConstructorReturn(t, _isNativeReflectConstruct() ? Reflect.construct(o, e || [], _getPrototypeOf(t).constructor) : o.apply(t, e)); }
function _possibleConstructorReturn(t, e) { if (e && ("object" == _typeof(e) || "function" == typeof e)) return e; if (void 0 !== e) throw new TypeError("Derived constructors may only return object or undefined"); return _assertThisInitialized(t); }
function _assertThisInitialized(e) { if (void 0 === e) throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); return e; }
function _isNativeReflectConstruct() { try { var t = !Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); } catch (t) {} return (_isNativeReflectConstruct = function _isNativeReflectConstruct() { return !!t; })(); }
function _getPrototypeOf(t) { return _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf.bind() : function (t) { return t.__proto__ || Object.getPrototypeOf(t); }, _getPrototypeOf(t); }
function _inherits(t, e) { if ("function" != typeof e && null !== e) throw new TypeError("Super expression must either be null or a function"); t.prototype = Object.create(e && e.prototype, { constructor: { value: t, writable: !0, configurable: !0 } }), Object.defineProperty(t, "prototype", { writable: !1 }), e && _setPrototypeOf(t, e); }
function _setPrototypeOf(t, e) { return _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function (t, e) { return t.__proto__ = e, t; }, _setPrototypeOf(t, e); }






















var __classPrivateFieldSet = undefined && undefined.__classPrivateFieldSet || function (receiver, state, value, kind, f) {
  if (kind === "m") throw new TypeError("Private method is not writable");
  if (kind === "a" && !f) throw new TypeError("Private accessor was defined without a setter");
  if (typeof state === "function" ? receiver !== state || !f : !state.has(receiver)) throw new TypeError("Cannot write private member to an object whose class did not declare it");
  return kind === "a" ? f.call(receiver, value) : f ? f.value = value : state.set(receiver, value), value;
};
var __classPrivateFieldGet = undefined && undefined.__classPrivateFieldGet || function (receiver, state, kind, f) {
  if (kind === "a" && !f) throw new TypeError("Private accessor was defined without a getter");
  if (typeof state === "function" ? receiver !== state || !f : !state.has(receiver)) throw new TypeError("Cannot read private member from an object whose class did not declare it");
  return kind === "m" ? f : kind === "a" ? f.call(receiver) : f ? f.value : state.get(receiver);
};
var _default_1_index;

/*
 * Stimulus-Controller für dynamische Symfony CollectionType-Felder.
 * Ermöglicht das Hinzufügen und Entfernen von Einträgen.
 */
var default_1 = /*#__PURE__*/function (_Controller) {
  function default_1() {
    var _this;
    _classCallCheck(this, default_1);
    _this = _callSuper(this, default_1, arguments);
    _default_1_index.set(_this, void 0);
    return _this;
  }
  _inherits(default_1, _Controller);
  return _createClass(default_1, [{
    key: "connect",
    value: function connect() {
      __classPrivateFieldSet(this, _default_1_index, this.entryTargets.length, "f");
    }
  }, {
    key: "addEntry",
    value: function addEntry() {
      var _a;
      var html = this.prototypeValue.replace(/__name__/g, String(__classPrivateFieldGet(this, _default_1_index, "f")));
      __classPrivateFieldSet(this, _default_1_index, (_a = __classPrivateFieldGet(this, _default_1_index, "f"), _a++, _a), "f");
      var wrapper = document.createElement('div');
      wrapper.classList.add('flex', 'items-center', 'gap-2');
      wrapper.setAttribute('data-collection-form-target', 'entry');
      wrapper.innerHTML = html + '<button type="button" data-action="collection-form#removeEntry" ' + 'class="text-red-500 hover:text-red-700 text-sm font-bold px-2 py-1 shrink-0 transition">' + "\u2715</button>";
      this.entriesTarget.appendChild(wrapper);
    }
  }, {
    key: "removeEntry",
    value: function removeEntry(event) {
      var target = event.target;
      var entry = target.closest('[data-collection-form-target="entry"]');
      if (entry) {
        entry.remove();
      }
    }
  }]);
}(_hotwired_stimulus__WEBPACK_IMPORTED_MODULE_22__.Controller);
_default_1_index = new WeakMap();
default_1.targets = ['entries', 'entry'];
default_1.values = {
  prototype: String
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (default_1);

/***/ },

/***/ "./node_modules/@symfony/stimulus-bridge/lazy-controller-loader.js!./assets/controllers/cookie_consent_controller.ts"
/*!***************************************************************************************************************************!*\
  !*** ./node_modules/@symfony/stimulus-bridge/lazy-controller-loader.js!./assets/controllers/cookie_consent_controller.ts ***!
  \***************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! core-js/modules/es.symbol.js */ "./node_modules/core-js/modules/es.symbol.js");
/* harmony import */ var core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! core-js/modules/es.symbol.description.js */ "./node_modules/core-js/modules/es.symbol.description.js");
/* harmony import */ var core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! core-js/modules/es.symbol.iterator.js */ "./node_modules/core-js/modules/es.symbol.iterator.js");
/* harmony import */ var core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var core_js_modules_es_symbol_to_primitive_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! core-js/modules/es.symbol.to-primitive.js */ "./node_modules/core-js/modules/es.symbol.to-primitive.js");
/* harmony import */ var core_js_modules_es_symbol_to_primitive_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_to_primitive_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var core_js_modules_es_error_cause_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! core-js/modules/es.error.cause.js */ "./node_modules/core-js/modules/es.error.cause.js");
/* harmony import */ var core_js_modules_es_error_cause_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_error_cause_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var core_js_modules_es_error_to_string_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! core-js/modules/es.error.to-string.js */ "./node_modules/core-js/modules/es.error.to-string.js");
/* harmony import */ var core_js_modules_es_error_to_string_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_error_to_string_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var core_js_modules_es_array_concat_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! core-js/modules/es.array.concat.js */ "./node_modules/core-js/modules/es.array.concat.js");
/* harmony import */ var core_js_modules_es_array_concat_js__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_concat_js__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! core-js/modules/es.array.iterator.js */ "./node_modules/core-js/modules/es.array.iterator.js");
/* harmony import */ var core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var core_js_modules_es_date_to_primitive_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! core-js/modules/es.date.to-primitive.js */ "./node_modules/core-js/modules/es.date.to-primitive.js");
/* harmony import */ var core_js_modules_es_date_to_primitive_js__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_date_to_primitive_js__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var core_js_modules_es_function_bind_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! core-js/modules/es.function.bind.js */ "./node_modules/core-js/modules/es.function.bind.js");
/* harmony import */ var core_js_modules_es_function_bind_js__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_function_bind_js__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var core_js_modules_es_number_constructor_js__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! core-js/modules/es.number.constructor.js */ "./node_modules/core-js/modules/es.number.constructor.js");
/* harmony import */ var core_js_modules_es_number_constructor_js__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_number_constructor_js__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var core_js_modules_es_object_create_js__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! core-js/modules/es.object.create.js */ "./node_modules/core-js/modules/es.object.create.js");
/* harmony import */ var core_js_modules_es_object_create_js__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_create_js__WEBPACK_IMPORTED_MODULE_11__);
/* harmony import */ var core_js_modules_es_object_define_property_js__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! core-js/modules/es.object.define-property.js */ "./node_modules/core-js/modules/es.object.define-property.js");
/* harmony import */ var core_js_modules_es_object_define_property_js__WEBPACK_IMPORTED_MODULE_12___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_define_property_js__WEBPACK_IMPORTED_MODULE_12__);
/* harmony import */ var core_js_modules_es_object_get_prototype_of_js__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! core-js/modules/es.object.get-prototype-of.js */ "./node_modules/core-js/modules/es.object.get-prototype-of.js");
/* harmony import */ var core_js_modules_es_object_get_prototype_of_js__WEBPACK_IMPORTED_MODULE_13___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_get_prototype_of_js__WEBPACK_IMPORTED_MODULE_13__);
/* harmony import */ var core_js_modules_es_object_proto_js__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! core-js/modules/es.object.proto.js */ "./node_modules/core-js/modules/es.object.proto.js");
/* harmony import */ var core_js_modules_es_object_proto_js__WEBPACK_IMPORTED_MODULE_14___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_proto_js__WEBPACK_IMPORTED_MODULE_14__);
/* harmony import */ var core_js_modules_es_object_set_prototype_of_js__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! core-js/modules/es.object.set-prototype-of.js */ "./node_modules/core-js/modules/es.object.set-prototype-of.js");
/* harmony import */ var core_js_modules_es_object_set_prototype_of_js__WEBPACK_IMPORTED_MODULE_15___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_set_prototype_of_js__WEBPACK_IMPORTED_MODULE_15__);
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! core-js/modules/es.object.to-string.js */ "./node_modules/core-js/modules/es.object.to-string.js");
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_16___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_16__);
/* harmony import */ var core_js_modules_es_reflect_construct_js__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! core-js/modules/es.reflect.construct.js */ "./node_modules/core-js/modules/es.reflect.construct.js");
/* harmony import */ var core_js_modules_es_reflect_construct_js__WEBPACK_IMPORTED_MODULE_17___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_reflect_construct_js__WEBPACK_IMPORTED_MODULE_17__);
/* harmony import */ var core_js_modules_es_regexp_constructor_js__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! core-js/modules/es.regexp.constructor.js */ "./node_modules/core-js/modules/es.regexp.constructor.js");
/* harmony import */ var core_js_modules_es_regexp_constructor_js__WEBPACK_IMPORTED_MODULE_18___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_regexp_constructor_js__WEBPACK_IMPORTED_MODULE_18__);
/* harmony import */ var core_js_modules_es_regexp_dot_all_js__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__(/*! core-js/modules/es.regexp.dot-all.js */ "./node_modules/core-js/modules/es.regexp.dot-all.js");
/* harmony import */ var core_js_modules_es_regexp_dot_all_js__WEBPACK_IMPORTED_MODULE_19___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_regexp_dot_all_js__WEBPACK_IMPORTED_MODULE_19__);
/* harmony import */ var core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_20__ = __webpack_require__(/*! core-js/modules/es.regexp.exec.js */ "./node_modules/core-js/modules/es.regexp.exec.js");
/* harmony import */ var core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_20___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_20__);
/* harmony import */ var core_js_modules_es_regexp_sticky_js__WEBPACK_IMPORTED_MODULE_21__ = __webpack_require__(/*! core-js/modules/es.regexp.sticky.js */ "./node_modules/core-js/modules/es.regexp.sticky.js");
/* harmony import */ var core_js_modules_es_regexp_sticky_js__WEBPACK_IMPORTED_MODULE_21___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_regexp_sticky_js__WEBPACK_IMPORTED_MODULE_21__);
/* harmony import */ var core_js_modules_es_regexp_to_string_js__WEBPACK_IMPORTED_MODULE_22__ = __webpack_require__(/*! core-js/modules/es.regexp.to-string.js */ "./node_modules/core-js/modules/es.regexp.to-string.js");
/* harmony import */ var core_js_modules_es_regexp_to_string_js__WEBPACK_IMPORTED_MODULE_22___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_regexp_to_string_js__WEBPACK_IMPORTED_MODULE_22__);
/* harmony import */ var core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_23__ = __webpack_require__(/*! core-js/modules/es.string.iterator.js */ "./node_modules/core-js/modules/es.string.iterator.js");
/* harmony import */ var core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_23___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_23__);
/* harmony import */ var core_js_modules_es_string_match_js__WEBPACK_IMPORTED_MODULE_24__ = __webpack_require__(/*! core-js/modules/es.string.match.js */ "./node_modules/core-js/modules/es.string.match.js");
/* harmony import */ var core_js_modules_es_string_match_js__WEBPACK_IMPORTED_MODULE_24___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_string_match_js__WEBPACK_IMPORTED_MODULE_24__);
/* harmony import */ var core_js_modules_es_string_replace_js__WEBPACK_IMPORTED_MODULE_25__ = __webpack_require__(/*! core-js/modules/es.string.replace.js */ "./node_modules/core-js/modules/es.string.replace.js");
/* harmony import */ var core_js_modules_es_string_replace_js__WEBPACK_IMPORTED_MODULE_25___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_string_replace_js__WEBPACK_IMPORTED_MODULE_25__);
/* harmony import */ var core_js_modules_es_weak_set_js__WEBPACK_IMPORTED_MODULE_26__ = __webpack_require__(/*! core-js/modules/es.weak-set.js */ "./node_modules/core-js/modules/es.weak-set.js");
/* harmony import */ var core_js_modules_es_weak_set_js__WEBPACK_IMPORTED_MODULE_26___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_weak_set_js__WEBPACK_IMPORTED_MODULE_26__);
/* harmony import */ var core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_27__ = __webpack_require__(/*! core-js/modules/web.dom-collections.iterator.js */ "./node_modules/core-js/modules/web.dom-collections.iterator.js");
/* harmony import */ var core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_27___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_27__);
/* harmony import */ var _hotwired_stimulus__WEBPACK_IMPORTED_MODULE_28__ = __webpack_require__(/*! @hotwired/stimulus */ "./node_modules/@hotwired/stimulus/dist/stimulus.js");
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _classCallCheck(a, n) { if (!(a instanceof n)) throw new TypeError("Cannot call a class as a function"); }
function _defineProperties(e, r) { for (var t = 0; t < r.length; t++) { var o = r[t]; o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(e, _toPropertyKey(o.key), o); } }
function _createClass(e, r, t) { return r && _defineProperties(e.prototype, r), t && _defineProperties(e, t), Object.defineProperty(e, "prototype", { writable: !1 }), e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != _typeof(t) || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }
function _callSuper(t, o, e) { return o = _getPrototypeOf(o), _possibleConstructorReturn(t, _isNativeReflectConstruct() ? Reflect.construct(o, e || [], _getPrototypeOf(t).constructor) : o.apply(t, e)); }
function _possibleConstructorReturn(t, e) { if (e && ("object" == _typeof(e) || "function" == typeof e)) return e; if (void 0 !== e) throw new TypeError("Derived constructors may only return object or undefined"); return _assertThisInitialized(t); }
function _assertThisInitialized(e) { if (void 0 === e) throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); return e; }
function _isNativeReflectConstruct() { try { var t = !Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); } catch (t) {} return (_isNativeReflectConstruct = function _isNativeReflectConstruct() { return !!t; })(); }
function _getPrototypeOf(t) { return _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf.bind() : function (t) { return t.__proto__ || Object.getPrototypeOf(t); }, _getPrototypeOf(t); }
function _inherits(t, e) { if ("function" != typeof e && null !== e) throw new TypeError("Super expression must either be null or a function"); t.prototype = Object.create(e && e.prototype, { constructor: { value: t, writable: !0, configurable: !0 } }), Object.defineProperty(t, "prototype", { writable: !1 }), e && _setPrototypeOf(t, e); }
function _setPrototypeOf(t, e) { return _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function (t, e) { return t.__proto__ = e, t; }, _setPrototypeOf(t, e); }




























var __classPrivateFieldGet = undefined && undefined.__classPrivateFieldGet || function (receiver, state, kind, f) {
  if (kind === "a" && !f) throw new TypeError("Private accessor was defined without a getter");
  if (typeof state === "function" ? receiver !== state || !f : !state.has(receiver)) throw new TypeError("Cannot read private member from an object whose class did not declare it");
  return kind === "m" ? f : kind === "a" ? f.call(receiver) : f ? f.value : state.get(receiver);
};
var _default_1_instances, _default_1_show, _default_1_hide, _default_1_hasConsent, _default_1_setConsent, _default_1_readCookie;

/**
 * Cookie-Consent-Banner (Issue #82).
 *
 * Zeigt das Banner, wenn noch keine Wahl getroffen wurde, speichert die
 * Entscheidung (akzeptiert/abgelehnt) in einem langlebigen Cookie und lässt sich
 * über den Footer-Link "Cookie-Einstellungen" erneut öffnen.
 *
 * Der Footer-Link liegt außerhalb des Banner-Elements und ist daher eine eigene
 * Controller-Instanz: sein Klick ruft `openSettings()` auf, das ein Fenster-Event
 * (`cookie-consent:open`) anstößt. Die Banner-Instanz fängt es über den
 * `@window`-Action-Descriptor ab (`reopen`). So bleibt die Stimulus-Event-Delegation
 * intakt – auch wenn Footer oder Banner einzeln (z. B. per Turbo-Frame) neu geladen
 * werden.
 */
var default_1 = /*#__PURE__*/function (_Controller) {
  function default_1() {
    var _this;
    _classCallCheck(this, default_1);
    _this = _callSuper(this, default_1, arguments);
    _default_1_instances.add(_this);
    return _this;
  }
  _inherits(default_1, _Controller);
  return _createClass(default_1, [{
    key: "connect",
    value: function connect() {
      if (this.hasBannerTarget && !__classPrivateFieldGet(this, _default_1_instances, "m", _default_1_hasConsent).call(this)) {
        __classPrivateFieldGet(this, _default_1_instances, "m", _default_1_show).call(this);
      }
    }
  }, {
    key: "accept",
    value: function accept() {
      __classPrivateFieldGet(this, _default_1_instances, "m", _default_1_setConsent).call(this, 'accepted');
      __classPrivateFieldGet(this, _default_1_instances, "m", _default_1_hide).call(this);
    }
  }, {
    key: "decline",
    value: function decline() {
      __classPrivateFieldGet(this, _default_1_instances, "m", _default_1_setConsent).call(this, 'declined');
      __classPrivateFieldGet(this, _default_1_instances, "m", _default_1_hide).call(this);
    }
    // Footer-Instanz: stößt ein Fenster-Event an, das die Banner-Instanz abfängt.
  }, {
    key: "openSettings",
    value: function openSettings() {
      this.dispatch('open');
    }
    // Banner-Instanz: reagiert auf das Fenster-Event (cookie-consent:open@window).
  }, {
    key: "reopen",
    value: function reopen() {
      if (this.hasBannerTarget) {
        __classPrivateFieldGet(this, _default_1_instances, "m", _default_1_show).call(this);
        // Nutzergetriggert (Klick auf "Cookie-Einstellungen"): der Fokus soll in
        // den Banner. Beim automatischen Erscheinen (connect) NICHT – dort zöge
        // der Fokus-Fang den ersten Tab in den Banner, und der Skip-Link wäre
        // nicht mehr das erste Tab-Ziel (BF-74, WCAG 2.4.1).
        this.bannerTarget.focus();
      }
    }
  }]);
}(_hotwired_stimulus__WEBPACK_IMPORTED_MODULE_28__.Controller);
_default_1_instances = new WeakSet(), _default_1_show = function _default_1_show() {
  this.bannerTarget.classList.remove('hidden');
}, _default_1_hide = function _default_1_hide() {
  this.bannerTarget.classList.add('hidden');
}, _default_1_hasConsent = function _default_1_hasConsent() {
  return __classPrivateFieldGet(this, _default_1_instances, "m", _default_1_readCookie).call(this, this.cookieNameValue) !== null;
}, _default_1_setConsent = function _default_1_setConsent(value) {
  var maxAge = this.lifetimeValue * 24 * 60 * 60;
  var cookie = "".concat(this.cookieNameValue, "=").concat(value, "; path=/; max-age=").concat(maxAge, "; samesite=lax");
  document.cookie = window.location.protocol === 'https:' ? "".concat(cookie, "; secure") : cookie;
}, _default_1_readCookie = function _default_1_readCookie(name) {
  var escaped = name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  var match = document.cookie.match(new RegExp('(?:^|; )' + escaped + '=([^;]*)'));
  return match ? decodeURIComponent(match[1]) : null;
};
default_1.targets = ['banner'];
default_1.values = {
  cookieName: {
    type: String,
    "default": 'cookie_consent'
  },
  lifetime: {
    type: Number,
    "default": 365
  }
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (default_1);

/***/ },

/***/ "./node_modules/@symfony/stimulus-bridge/lazy-controller-loader.js!./assets/controllers/csrf_protection_controller.ts"
/*!****************************************************************************************************************************!*\
  !*** ./node_modules/@symfony/stimulus-bridge/lazy-controller-loader.js!./assets/controllers/csrf_protection_controller.ts ***!
  \****************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ controller)
/* harmony export */ });
/* harmony import */ var _hotwired_stimulus__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @hotwired/stimulus */ "./node_modules/@hotwired/stimulus/dist/stimulus.js");

const controller = class extends _hotwired_stimulus__WEBPACK_IMPORTED_MODULE_0__.Controller {
    constructor(context) {
        super(context);
        this.__stimulusLazyController = true;
    }
    initialize() {
        if (this.application.controllers.find((controller) => {
            return controller.identifier === this.identifier && controller.__stimulusLazyController;
        })) {
            return;
        }
        Promise.all(/*! import() */[__webpack_require__.e("vendors-node_modules_core-js_modules_es_array-buffer_constructor_js-node_modules_core-js_modu-cebe39"), __webpack_require__.e("assets_controllers_csrf_protection_controller_ts")]).then(__webpack_require__.bind(__webpack_require__, /*! ./assets/controllers/csrf_protection_controller.ts */ "./assets/controllers/csrf_protection_controller.ts")).then((controller) => {
            this.application.register(this.identifier, controller.default);
        });
    }
};


/***/ },

/***/ "./node_modules/@symfony/stimulus-bridge/lazy-controller-loader.js!./assets/controllers/hello_controller.ts"
/*!******************************************************************************************************************!*\
  !*** ./node_modules/@symfony/stimulus-bridge/lazy-controller-loader.js!./assets/controllers/hello_controller.ts ***!
  \******************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ _default)
/* harmony export */ });
/* harmony import */ var core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! core-js/modules/es.symbol.js */ "./node_modules/core-js/modules/es.symbol.js");
/* harmony import */ var core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! core-js/modules/es.symbol.description.js */ "./node_modules/core-js/modules/es.symbol.description.js");
/* harmony import */ var core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! core-js/modules/es.symbol.iterator.js */ "./node_modules/core-js/modules/es.symbol.iterator.js");
/* harmony import */ var core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var core_js_modules_es_symbol_to_primitive_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! core-js/modules/es.symbol.to-primitive.js */ "./node_modules/core-js/modules/es.symbol.to-primitive.js");
/* harmony import */ var core_js_modules_es_symbol_to_primitive_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_to_primitive_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var core_js_modules_es_error_cause_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! core-js/modules/es.error.cause.js */ "./node_modules/core-js/modules/es.error.cause.js");
/* harmony import */ var core_js_modules_es_error_cause_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_error_cause_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var core_js_modules_es_error_to_string_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! core-js/modules/es.error.to-string.js */ "./node_modules/core-js/modules/es.error.to-string.js");
/* harmony import */ var core_js_modules_es_error_to_string_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_error_to_string_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! core-js/modules/es.array.iterator.js */ "./node_modules/core-js/modules/es.array.iterator.js");
/* harmony import */ var core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var core_js_modules_es_date_to_primitive_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! core-js/modules/es.date.to-primitive.js */ "./node_modules/core-js/modules/es.date.to-primitive.js");
/* harmony import */ var core_js_modules_es_date_to_primitive_js__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_date_to_primitive_js__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var core_js_modules_es_function_bind_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! core-js/modules/es.function.bind.js */ "./node_modules/core-js/modules/es.function.bind.js");
/* harmony import */ var core_js_modules_es_function_bind_js__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_function_bind_js__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var core_js_modules_es_number_constructor_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! core-js/modules/es.number.constructor.js */ "./node_modules/core-js/modules/es.number.constructor.js");
/* harmony import */ var core_js_modules_es_number_constructor_js__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_number_constructor_js__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var core_js_modules_es_object_create_js__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! core-js/modules/es.object.create.js */ "./node_modules/core-js/modules/es.object.create.js");
/* harmony import */ var core_js_modules_es_object_create_js__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_create_js__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var core_js_modules_es_object_define_property_js__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! core-js/modules/es.object.define-property.js */ "./node_modules/core-js/modules/es.object.define-property.js");
/* harmony import */ var core_js_modules_es_object_define_property_js__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_define_property_js__WEBPACK_IMPORTED_MODULE_11__);
/* harmony import */ var core_js_modules_es_object_get_prototype_of_js__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! core-js/modules/es.object.get-prototype-of.js */ "./node_modules/core-js/modules/es.object.get-prototype-of.js");
/* harmony import */ var core_js_modules_es_object_get_prototype_of_js__WEBPACK_IMPORTED_MODULE_12___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_get_prototype_of_js__WEBPACK_IMPORTED_MODULE_12__);
/* harmony import */ var core_js_modules_es_object_proto_js__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! core-js/modules/es.object.proto.js */ "./node_modules/core-js/modules/es.object.proto.js");
/* harmony import */ var core_js_modules_es_object_proto_js__WEBPACK_IMPORTED_MODULE_13___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_proto_js__WEBPACK_IMPORTED_MODULE_13__);
/* harmony import */ var core_js_modules_es_object_set_prototype_of_js__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! core-js/modules/es.object.set-prototype-of.js */ "./node_modules/core-js/modules/es.object.set-prototype-of.js");
/* harmony import */ var core_js_modules_es_object_set_prototype_of_js__WEBPACK_IMPORTED_MODULE_14___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_set_prototype_of_js__WEBPACK_IMPORTED_MODULE_14__);
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! core-js/modules/es.object.to-string.js */ "./node_modules/core-js/modules/es.object.to-string.js");
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_15___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_15__);
/* harmony import */ var core_js_modules_es_reflect_construct_js__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! core-js/modules/es.reflect.construct.js */ "./node_modules/core-js/modules/es.reflect.construct.js");
/* harmony import */ var core_js_modules_es_reflect_construct_js__WEBPACK_IMPORTED_MODULE_16___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_reflect_construct_js__WEBPACK_IMPORTED_MODULE_16__);
/* harmony import */ var core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! core-js/modules/es.string.iterator.js */ "./node_modules/core-js/modules/es.string.iterator.js");
/* harmony import */ var core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_17___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_17__);
/* harmony import */ var core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! core-js/modules/web.dom-collections.iterator.js */ "./node_modules/core-js/modules/web.dom-collections.iterator.js");
/* harmony import */ var core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_18___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_18__);
/* harmony import */ var _hotwired_stimulus__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__(/*! @hotwired/stimulus */ "./node_modules/@hotwired/stimulus/dist/stimulus.js");
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }



















function _classCallCheck(a, n) { if (!(a instanceof n)) throw new TypeError("Cannot call a class as a function"); }
function _defineProperties(e, r) { for (var t = 0; t < r.length; t++) { var o = r[t]; o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(e, _toPropertyKey(o.key), o); } }
function _createClass(e, r, t) { return r && _defineProperties(e.prototype, r), t && _defineProperties(e, t), Object.defineProperty(e, "prototype", { writable: !1 }), e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != _typeof(t) || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }
function _callSuper(t, o, e) { return o = _getPrototypeOf(o), _possibleConstructorReturn(t, _isNativeReflectConstruct() ? Reflect.construct(o, e || [], _getPrototypeOf(t).constructor) : o.apply(t, e)); }
function _possibleConstructorReturn(t, e) { if (e && ("object" == _typeof(e) || "function" == typeof e)) return e; if (void 0 !== e) throw new TypeError("Derived constructors may only return object or undefined"); return _assertThisInitialized(t); }
function _assertThisInitialized(e) { if (void 0 === e) throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); return e; }
function _isNativeReflectConstruct() { try { var t = !Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); } catch (t) {} return (_isNativeReflectConstruct = function _isNativeReflectConstruct() { return !!t; })(); }
function _getPrototypeOf(t) { return _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf.bind() : function (t) { return t.__proto__ || Object.getPrototypeOf(t); }, _getPrototypeOf(t); }
function _inherits(t, e) { if ("function" != typeof e && null !== e) throw new TypeError("Super expression must either be null or a function"); t.prototype = Object.create(e && e.prototype, { constructor: { value: t, writable: !0, configurable: !0 } }), Object.defineProperty(t, "prototype", { writable: !1 }), e && _setPrototypeOf(t, e); }
function _setPrototypeOf(t, e) { return _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function (t, e) { return t.__proto__ = e, t; }, _setPrototypeOf(t, e); }

/*
 * This is an example Stimulus controller!
 *
 * Any element with a data-controller="hello" attribute will cause
 * this controller to be executed. The name "hello" comes from the filename:
 * hello_controller.ts -> "hello"
 *
 * Delete this file or adapt it for your use!
 */
var _default = /*#__PURE__*/function (_Controller) {
  function _default() {
    _classCallCheck(this, _default);
    return _callSuper(this, _default, arguments);
  }
  _inherits(_default, _Controller);
  return _createClass(_default, [{
    key: "connect",
    value: function connect() {
      this.element.textContent = 'Hello Stimulus! Edit me in assets/controllers/hello_controller.ts';
    }
  }]);
}(_hotwired_stimulus__WEBPACK_IMPORTED_MODULE_19__.Controller);


/***/ },

/***/ "./node_modules/@symfony/stimulus-bridge/lazy-controller-loader.js!./assets/controllers/image_sort_controller.ts"
/*!***********************************************************************************************************************!*\
  !*** ./node_modules/@symfony/stimulus-bridge/lazy-controller-loader.js!./assets/controllers/image_sort_controller.ts ***!
  \***********************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! core-js/modules/es.symbol.js */ "./node_modules/core-js/modules/es.symbol.js");
/* harmony import */ var core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! core-js/modules/es.symbol.description.js */ "./node_modules/core-js/modules/es.symbol.description.js");
/* harmony import */ var core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! core-js/modules/es.symbol.iterator.js */ "./node_modules/core-js/modules/es.symbol.iterator.js");
/* harmony import */ var core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var core_js_modules_es_symbol_to_primitive_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! core-js/modules/es.symbol.to-primitive.js */ "./node_modules/core-js/modules/es.symbol.to-primitive.js");
/* harmony import */ var core_js_modules_es_symbol_to_primitive_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_to_primitive_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var core_js_modules_es_error_cause_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! core-js/modules/es.error.cause.js */ "./node_modules/core-js/modules/es.error.cause.js");
/* harmony import */ var core_js_modules_es_error_cause_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_error_cause_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var core_js_modules_es_error_to_string_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! core-js/modules/es.error.to-string.js */ "./node_modules/core-js/modules/es.error.to-string.js");
/* harmony import */ var core_js_modules_es_error_to_string_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_error_to_string_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var core_js_modules_es_array_for_each_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! core-js/modules/es.array.for-each.js */ "./node_modules/core-js/modules/es.array.for-each.js");
/* harmony import */ var core_js_modules_es_array_for_each_js__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_for_each_js__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var core_js_modules_es_array_from_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! core-js/modules/es.array.from.js */ "./node_modules/core-js/modules/es.array.from.js");
/* harmony import */ var core_js_modules_es_array_from_js__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_from_js__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! core-js/modules/es.array.iterator.js */ "./node_modules/core-js/modules/es.array.iterator.js");
/* harmony import */ var core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! core-js/modules/es.array.map.js */ "./node_modules/core-js/modules/es.array.map.js");
/* harmony import */ var core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var core_js_modules_es_date_to_json_js__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! core-js/modules/es.date.to-json.js */ "./node_modules/core-js/modules/es.date.to-json.js");
/* harmony import */ var core_js_modules_es_date_to_json_js__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_date_to_json_js__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var core_js_modules_es_date_to_primitive_js__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! core-js/modules/es.date.to-primitive.js */ "./node_modules/core-js/modules/es.date.to-primitive.js");
/* harmony import */ var core_js_modules_es_date_to_primitive_js__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_date_to_primitive_js__WEBPACK_IMPORTED_MODULE_11__);
/* harmony import */ var core_js_modules_es_function_bind_js__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! core-js/modules/es.function.bind.js */ "./node_modules/core-js/modules/es.function.bind.js");
/* harmony import */ var core_js_modules_es_function_bind_js__WEBPACK_IMPORTED_MODULE_12___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_function_bind_js__WEBPACK_IMPORTED_MODULE_12__);
/* harmony import */ var core_js_modules_es_json_stringify_js__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! core-js/modules/es.json.stringify.js */ "./node_modules/core-js/modules/es.json.stringify.js");
/* harmony import */ var core_js_modules_es_json_stringify_js__WEBPACK_IMPORTED_MODULE_13___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_json_stringify_js__WEBPACK_IMPORTED_MODULE_13__);
/* harmony import */ var core_js_modules_es_number_constructor_js__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! core-js/modules/es.number.constructor.js */ "./node_modules/core-js/modules/es.number.constructor.js");
/* harmony import */ var core_js_modules_es_number_constructor_js__WEBPACK_IMPORTED_MODULE_14___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_number_constructor_js__WEBPACK_IMPORTED_MODULE_14__);
/* harmony import */ var core_js_modules_es_object_create_js__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! core-js/modules/es.object.create.js */ "./node_modules/core-js/modules/es.object.create.js");
/* harmony import */ var core_js_modules_es_object_create_js__WEBPACK_IMPORTED_MODULE_15___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_create_js__WEBPACK_IMPORTED_MODULE_15__);
/* harmony import */ var core_js_modules_es_object_define_property_js__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! core-js/modules/es.object.define-property.js */ "./node_modules/core-js/modules/es.object.define-property.js");
/* harmony import */ var core_js_modules_es_object_define_property_js__WEBPACK_IMPORTED_MODULE_16___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_define_property_js__WEBPACK_IMPORTED_MODULE_16__);
/* harmony import */ var core_js_modules_es_object_get_prototype_of_js__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! core-js/modules/es.object.get-prototype-of.js */ "./node_modules/core-js/modules/es.object.get-prototype-of.js");
/* harmony import */ var core_js_modules_es_object_get_prototype_of_js__WEBPACK_IMPORTED_MODULE_17___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_get_prototype_of_js__WEBPACK_IMPORTED_MODULE_17__);
/* harmony import */ var core_js_modules_es_object_proto_js__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! core-js/modules/es.object.proto.js */ "./node_modules/core-js/modules/es.object.proto.js");
/* harmony import */ var core_js_modules_es_object_proto_js__WEBPACK_IMPORTED_MODULE_18___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_proto_js__WEBPACK_IMPORTED_MODULE_18__);
/* harmony import */ var core_js_modules_es_object_set_prototype_of_js__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__(/*! core-js/modules/es.object.set-prototype-of.js */ "./node_modules/core-js/modules/es.object.set-prototype-of.js");
/* harmony import */ var core_js_modules_es_object_set_prototype_of_js__WEBPACK_IMPORTED_MODULE_19___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_set_prototype_of_js__WEBPACK_IMPORTED_MODULE_19__);
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_20__ = __webpack_require__(/*! core-js/modules/es.object.to-string.js */ "./node_modules/core-js/modules/es.object.to-string.js");
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_20___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_20__);
/* harmony import */ var core_js_modules_es_promise_js__WEBPACK_IMPORTED_MODULE_21__ = __webpack_require__(/*! core-js/modules/es.promise.js */ "./node_modules/core-js/modules/es.promise.js");
/* harmony import */ var core_js_modules_es_promise_js__WEBPACK_IMPORTED_MODULE_21___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_promise_js__WEBPACK_IMPORTED_MODULE_21__);
/* harmony import */ var core_js_modules_es_reflect_construct_js__WEBPACK_IMPORTED_MODULE_22__ = __webpack_require__(/*! core-js/modules/es.reflect.construct.js */ "./node_modules/core-js/modules/es.reflect.construct.js");
/* harmony import */ var core_js_modules_es_reflect_construct_js__WEBPACK_IMPORTED_MODULE_22___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_reflect_construct_js__WEBPACK_IMPORTED_MODULE_22__);
/* harmony import */ var core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_23__ = __webpack_require__(/*! core-js/modules/es.string.iterator.js */ "./node_modules/core-js/modules/es.string.iterator.js");
/* harmony import */ var core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_23___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_23__);
/* harmony import */ var core_js_modules_es_weak_set_js__WEBPACK_IMPORTED_MODULE_24__ = __webpack_require__(/*! core-js/modules/es.weak-set.js */ "./node_modules/core-js/modules/es.weak-set.js");
/* harmony import */ var core_js_modules_es_weak_set_js__WEBPACK_IMPORTED_MODULE_24___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_weak_set_js__WEBPACK_IMPORTED_MODULE_24__);
/* harmony import */ var core_js_modules_esnext_iterator_constructor_js__WEBPACK_IMPORTED_MODULE_25__ = __webpack_require__(/*! core-js/modules/esnext.iterator.constructor.js */ "./node_modules/core-js/modules/esnext.iterator.constructor.js");
/* harmony import */ var core_js_modules_esnext_iterator_constructor_js__WEBPACK_IMPORTED_MODULE_25___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_esnext_iterator_constructor_js__WEBPACK_IMPORTED_MODULE_25__);
/* harmony import */ var core_js_modules_esnext_iterator_for_each_js__WEBPACK_IMPORTED_MODULE_26__ = __webpack_require__(/*! core-js/modules/esnext.iterator.for-each.js */ "./node_modules/core-js/modules/esnext.iterator.for-each.js");
/* harmony import */ var core_js_modules_esnext_iterator_for_each_js__WEBPACK_IMPORTED_MODULE_26___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_esnext_iterator_for_each_js__WEBPACK_IMPORTED_MODULE_26__);
/* harmony import */ var core_js_modules_esnext_iterator_map_js__WEBPACK_IMPORTED_MODULE_27__ = __webpack_require__(/*! core-js/modules/esnext.iterator.map.js */ "./node_modules/core-js/modules/esnext.iterator.map.js");
/* harmony import */ var core_js_modules_esnext_iterator_map_js__WEBPACK_IMPORTED_MODULE_27___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_esnext_iterator_map_js__WEBPACK_IMPORTED_MODULE_27__);
/* harmony import */ var core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_28__ = __webpack_require__(/*! core-js/modules/web.dom-collections.for-each.js */ "./node_modules/core-js/modules/web.dom-collections.for-each.js");
/* harmony import */ var core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_28___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_28__);
/* harmony import */ var core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_29__ = __webpack_require__(/*! core-js/modules/web.dom-collections.iterator.js */ "./node_modules/core-js/modules/web.dom-collections.iterator.js");
/* harmony import */ var core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_29___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_29__);
/* harmony import */ var _hotwired_stimulus__WEBPACK_IMPORTED_MODULE_30__ = __webpack_require__(/*! @hotwired/stimulus */ "./node_modules/@hotwired/stimulus/dist/stimulus.js");
/* harmony import */ var sortablejs__WEBPACK_IMPORTED_MODULE_31__ = __webpack_require__(/*! sortablejs */ "./node_modules/sortablejs/modular/sortable.esm.js");
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _regenerator() { /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/babel/babel/blob/main/packages/babel-helpers/LICENSE */ var e, t, r = "function" == typeof Symbol ? Symbol : {}, n = r.iterator || "@@iterator", o = r.toStringTag || "@@toStringTag"; function i(r, n, o, i) { var c = n && n.prototype instanceof Generator ? n : Generator, u = Object.create(c.prototype); return _regeneratorDefine2(u, "_invoke", function (r, n, o) { var i, c, u, f = 0, p = o || [], y = !1, G = { p: 0, n: 0, v: e, a: d, f: d.bind(e, 4), d: function d(t, r) { return i = t, c = 0, u = e, G.n = r, a; } }; function d(r, n) { for (c = r, u = n, t = 0; !y && f && !o && t < p.length; t++) { var o, i = p[t], d = G.p, l = i[2]; r > 3 ? (o = l === n) && (u = i[(c = i[4]) ? 5 : (c = 3, 3)], i[4] = i[5] = e) : i[0] <= d && ((o = r < 2 && d < i[1]) ? (c = 0, G.v = n, G.n = i[1]) : d < l && (o = r < 3 || i[0] > n || n > l) && (i[4] = r, i[5] = n, G.n = l, c = 0)); } if (o || r > 1) return a; throw y = !0, n; } return function (o, p, l) { if (f > 1) throw TypeError("Generator is already running"); for (y && 1 === p && d(p, l), c = p, u = l; (t = c < 2 ? e : u) || !y;) { i || (c ? c < 3 ? (c > 1 && (G.n = -1), d(c, u)) : G.n = u : G.v = u); try { if (f = 2, i) { if (c || (o = "next"), t = i[o]) { if (!(t = t.call(i, u))) throw TypeError("iterator result is not an object"); if (!t.done) return t; u = t.value, c < 2 && (c = 0); } else 1 === c && (t = i["return"]) && t.call(i), c < 2 && (u = TypeError("The iterator does not provide a '" + o + "' method"), c = 1); i = e; } else if ((t = (y = G.n < 0) ? u : r.call(n, G)) !== a) break; } catch (t) { i = e, c = 1, u = t; } finally { f = 1; } } return { value: t, done: y }; }; }(r, o, i), !0), u; } var a = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} t = Object.getPrototypeOf; var c = [][n] ? t(t([][n]())) : (_regeneratorDefine2(t = {}, n, function () { return this; }), t), u = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(c); function f(e) { return Object.setPrototypeOf ? Object.setPrototypeOf(e, GeneratorFunctionPrototype) : (e.__proto__ = GeneratorFunctionPrototype, _regeneratorDefine2(e, o, "GeneratorFunction")), e.prototype = Object.create(u), e; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, _regeneratorDefine2(u, "constructor", GeneratorFunctionPrototype), _regeneratorDefine2(GeneratorFunctionPrototype, "constructor", GeneratorFunction), GeneratorFunction.displayName = "GeneratorFunction", _regeneratorDefine2(GeneratorFunctionPrototype, o, "GeneratorFunction"), _regeneratorDefine2(u), _regeneratorDefine2(u, o, "Generator"), _regeneratorDefine2(u, n, function () { return this; }), _regeneratorDefine2(u, "toString", function () { return "[object Generator]"; }), (_regenerator = function _regenerator() { return { w: i, m: f }; })(); }
function _regeneratorDefine2(e, r, n, t) { var i = Object.defineProperty; try { i({}, "", {}); } catch (e) { i = 0; } _regeneratorDefine2 = function _regeneratorDefine(e, r, n, t) { function o(r, n) { _regeneratorDefine2(e, r, function (e) { return this._invoke(r, n, e); }); } r ? i ? i(e, r, { value: n, enumerable: !t, configurable: !t, writable: !t }) : e[r] = n : (o("next", 0), o("throw", 1), o("return", 2)); }, _regeneratorDefine2(e, r, n, t); }
function asyncGeneratorStep(n, t, e, r, o, a, c) { try { var i = n[a](c), u = i.value; } catch (n) { return void e(n); } i.done ? t(u) : Promise.resolve(u).then(r, o); }
function _asyncToGenerator(n) { return function () { var t = this, e = arguments; return new Promise(function (r, o) { var a = n.apply(t, e); function _next(n) { asyncGeneratorStep(a, r, o, _next, _throw, "next", n); } function _throw(n) { asyncGeneratorStep(a, r, o, _next, _throw, "throw", n); } _next(void 0); }); }; }
function _classCallCheck(a, n) { if (!(a instanceof n)) throw new TypeError("Cannot call a class as a function"); }
function _defineProperties(e, r) { for (var t = 0; t < r.length; t++) { var o = r[t]; o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(e, _toPropertyKey(o.key), o); } }
function _createClass(e, r, t) { return r && _defineProperties(e.prototype, r), t && _defineProperties(e, t), Object.defineProperty(e, "prototype", { writable: !1 }), e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != _typeof(t) || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }
function _callSuper(t, o, e) { return o = _getPrototypeOf(o), _possibleConstructorReturn(t, _isNativeReflectConstruct() ? Reflect.construct(o, e || [], _getPrototypeOf(t).constructor) : o.apply(t, e)); }
function _possibleConstructorReturn(t, e) { if (e && ("object" == _typeof(e) || "function" == typeof e)) return e; if (void 0 !== e) throw new TypeError("Derived constructors may only return object or undefined"); return _assertThisInitialized(t); }
function _assertThisInitialized(e) { if (void 0 === e) throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); return e; }
function _isNativeReflectConstruct() { try { var t = !Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); } catch (t) {} return (_isNativeReflectConstruct = function _isNativeReflectConstruct() { return !!t; })(); }
function _getPrototypeOf(t) { return _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf.bind() : function (t) { return t.__proto__ || Object.getPrototypeOf(t); }, _getPrototypeOf(t); }
function _inherits(t, e) { if ("function" != typeof e && null !== e) throw new TypeError("Super expression must either be null or a function"); t.prototype = Object.create(e && e.prototype, { constructor: { value: t, writable: !0, configurable: !0 } }), Object.defineProperty(t, "prototype", { writable: !1 }), e && _setPrototypeOf(t, e); }
function _setPrototypeOf(t, e) { return _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function (t, e) { return t.__proto__ = e, t; }, _setPrototypeOf(t, e); }






























var __classPrivateFieldGet = undefined && undefined.__classPrivateFieldGet || function (receiver, state, kind, f) {
  if (kind === "a" && !f) throw new TypeError("Private accessor was defined without a getter");
  if (typeof state === "function" ? receiver !== state || !f : !state.has(receiver)) throw new TypeError("Cannot read private member from an object whose class did not declare it");
  return kind === "m" ? f : kind === "a" ? f.call(receiver) : f ? f.value : state.get(receiver);
};
var _default_1_instances, _default_1_afterMove, _default_1_updateButtons, _default_1_persist;


/*
 * Stimulus-Controller für die Bildsortierung.
 * Zwei gleichwertige Wege, beide senden die neue Reihenfolge per POST an
 * denselben Endpunkt (admin_restaurant_image_sort):
 *   1. Drag & Drop (Maus) via SortableJS.
 *   2. Auf/Ab-Knöpfe je Bild (Tastatur/ohne Ziehen) via moveUp/moveDown.
 */
var default_1 = /*#__PURE__*/function (_Controller) {
  function default_1() {
    var _this;
    _classCallCheck(this, default_1);
    _this = _callSuper(this, default_1, arguments);
    _default_1_instances.add(_this);
    return _this;
  }
  _inherits(default_1, _Controller);
  return _createClass(default_1, [{
    key: "connect",
    value: function connect() {
      var _this2 = this;
      sortablejs__WEBPACK_IMPORTED_MODULE_31__["default"].create(this.listTarget, {
        handle: '.drag-handle',
        ghostClass: 'opacity-30',
        animation: 150,
        onEnd: function onEnd() {
          __classPrivateFieldGet(_this2, _default_1_instances, "m", _default_1_updateButtons).call(_this2);
          void __classPrivateFieldGet(_this2, _default_1_instances, "m", _default_1_persist).call(_this2);
        }
      });
      __classPrivateFieldGet(this, _default_1_instances, "m", _default_1_updateButtons).call(this);
    }
  }, {
    key: "moveUp",
    value: function moveUp(event) {
      var button = event.currentTarget;
      var row = button.closest('[data-image-id]');
      var previous = row === null || row === void 0 ? void 0 : row.previousElementSibling;
      if (!row || !previous) {
        return;
      }
      previous.before(row);
      __classPrivateFieldGet(this, _default_1_instances, "m", _default_1_afterMove).call(this, button, row);
    }
  }, {
    key: "moveDown",
    value: function moveDown(event) {
      var button = event.currentTarget;
      var row = button.closest('[data-image-id]');
      var next = row === null || row === void 0 ? void 0 : row.nextElementSibling;
      if (!row || !next) {
        return;
      }
      next.after(row);
      __classPrivateFieldGet(this, _default_1_instances, "m", _default_1_afterMove).call(this, button, row);
    }
  }]);
}(_hotwired_stimulus__WEBPACK_IMPORTED_MODULE_30__.Controller);
_default_1_instances = new WeakSet(), _default_1_afterMove = function _default_1_afterMove(button, row) {
  __classPrivateFieldGet(this, _default_1_instances, "m", _default_1_updateButtons).call(this);
  if (button.disabled) {
    var fallback = row.querySelector('[data-sort-button]:not([disabled])');
    fallback === null || fallback === void 0 || fallback.focus();
  } else {
    button.focus();
  }
  void __classPrivateFieldGet(this, _default_1_instances, "m", _default_1_persist).call(this);
}, _default_1_updateButtons = function _default_1_updateButtons() {
  var rows = Array.from(this.listTarget.querySelectorAll('[data-image-id]'));
  rows.forEach(function (row, index) {
    var up = row.querySelector('[data-sort-button="up"]');
    var down = row.querySelector('[data-sort-button="down"]');
    if (up) {
      up.disabled = index === 0;
    }
    if (down) {
      down.disabled = index === rows.length - 1;
    }
  });
}, _default_1_persist = /*#__PURE__*/function () {
  var _default_1_persist2 = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee() {
    var items, imageIds;
    return _regenerator().w(function (_context) {
      while (1) switch (_context.n) {
        case 0:
          items = this.listTarget.querySelectorAll('[data-image-id]');
          imageIds = Array.from(items).map(function (el) {
            return Number(el.dataset.imageId);
          }); // Cover-Badge aktualisieren: nur beim ersten Element anzeigen
          items.forEach(function (el, index) {
            var badge = el.querySelector('[data-cover-badge]');
            if (badge) {
              badge.style.display = index === 0 ? '' : 'none';
            }
          });
          _context.n = 1;
          return fetch(this.urlValue, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            },
            body: JSON.stringify({
              _token: this.tokenValue,
              imageIds: imageIds
            })
          });
        case 1:
          return _context.a(2);
      }
    }, _callee, this);
  }));
  function _default_1_persist() {
    return _default_1_persist2.apply(this, arguments);
  }
  return _default_1_persist;
}();
default_1.targets = ['list'];
default_1.values = {
  url: String,
  token: String
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (default_1);

/***/ },

/***/ "./node_modules/@symfony/stimulus-bridge/lazy-controller-loader.js!./assets/controllers/language_switcher_controller.ts"
/*!******************************************************************************************************************************!*\
  !*** ./node_modules/@symfony/stimulus-bridge/lazy-controller-loader.js!./assets/controllers/language_switcher_controller.ts ***!
  \******************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! core-js/modules/es.symbol.js */ "./node_modules/core-js/modules/es.symbol.js");
/* harmony import */ var core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! core-js/modules/es.symbol.description.js */ "./node_modules/core-js/modules/es.symbol.description.js");
/* harmony import */ var core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! core-js/modules/es.symbol.iterator.js */ "./node_modules/core-js/modules/es.symbol.iterator.js");
/* harmony import */ var core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var core_js_modules_es_symbol_to_primitive_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! core-js/modules/es.symbol.to-primitive.js */ "./node_modules/core-js/modules/es.symbol.to-primitive.js");
/* harmony import */ var core_js_modules_es_symbol_to_primitive_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_to_primitive_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var core_js_modules_es_error_cause_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! core-js/modules/es.error.cause.js */ "./node_modules/core-js/modules/es.error.cause.js");
/* harmony import */ var core_js_modules_es_error_cause_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_error_cause_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var core_js_modules_es_error_to_string_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! core-js/modules/es.error.to-string.js */ "./node_modules/core-js/modules/es.error.to-string.js");
/* harmony import */ var core_js_modules_es_error_to_string_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_error_to_string_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! core-js/modules/es.array.iterator.js */ "./node_modules/core-js/modules/es.array.iterator.js");
/* harmony import */ var core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var core_js_modules_es_date_to_primitive_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! core-js/modules/es.date.to-primitive.js */ "./node_modules/core-js/modules/es.date.to-primitive.js");
/* harmony import */ var core_js_modules_es_date_to_primitive_js__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_date_to_primitive_js__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var core_js_modules_es_function_bind_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! core-js/modules/es.function.bind.js */ "./node_modules/core-js/modules/es.function.bind.js");
/* harmony import */ var core_js_modules_es_function_bind_js__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_function_bind_js__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var core_js_modules_es_number_constructor_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! core-js/modules/es.number.constructor.js */ "./node_modules/core-js/modules/es.number.constructor.js");
/* harmony import */ var core_js_modules_es_number_constructor_js__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_number_constructor_js__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var core_js_modules_es_object_create_js__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! core-js/modules/es.object.create.js */ "./node_modules/core-js/modules/es.object.create.js");
/* harmony import */ var core_js_modules_es_object_create_js__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_create_js__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var core_js_modules_es_object_define_property_js__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! core-js/modules/es.object.define-property.js */ "./node_modules/core-js/modules/es.object.define-property.js");
/* harmony import */ var core_js_modules_es_object_define_property_js__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_define_property_js__WEBPACK_IMPORTED_MODULE_11__);
/* harmony import */ var core_js_modules_es_object_get_prototype_of_js__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! core-js/modules/es.object.get-prototype-of.js */ "./node_modules/core-js/modules/es.object.get-prototype-of.js");
/* harmony import */ var core_js_modules_es_object_get_prototype_of_js__WEBPACK_IMPORTED_MODULE_12___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_get_prototype_of_js__WEBPACK_IMPORTED_MODULE_12__);
/* harmony import */ var core_js_modules_es_object_proto_js__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! core-js/modules/es.object.proto.js */ "./node_modules/core-js/modules/es.object.proto.js");
/* harmony import */ var core_js_modules_es_object_proto_js__WEBPACK_IMPORTED_MODULE_13___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_proto_js__WEBPACK_IMPORTED_MODULE_13__);
/* harmony import */ var core_js_modules_es_object_set_prototype_of_js__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! core-js/modules/es.object.set-prototype-of.js */ "./node_modules/core-js/modules/es.object.set-prototype-of.js");
/* harmony import */ var core_js_modules_es_object_set_prototype_of_js__WEBPACK_IMPORTED_MODULE_14___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_set_prototype_of_js__WEBPACK_IMPORTED_MODULE_14__);
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! core-js/modules/es.object.to-string.js */ "./node_modules/core-js/modules/es.object.to-string.js");
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_15___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_15__);
/* harmony import */ var core_js_modules_es_reflect_construct_js__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! core-js/modules/es.reflect.construct.js */ "./node_modules/core-js/modules/es.reflect.construct.js");
/* harmony import */ var core_js_modules_es_reflect_construct_js__WEBPACK_IMPORTED_MODULE_16___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_reflect_construct_js__WEBPACK_IMPORTED_MODULE_16__);
/* harmony import */ var core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! core-js/modules/es.string.iterator.js */ "./node_modules/core-js/modules/es.string.iterator.js");
/* harmony import */ var core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_17___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_17__);
/* harmony import */ var core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! core-js/modules/web.dom-collections.iterator.js */ "./node_modules/core-js/modules/web.dom-collections.iterator.js");
/* harmony import */ var core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_18___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_18__);
/* harmony import */ var _hotwired_stimulus__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__(/*! @hotwired/stimulus */ "./node_modules/@hotwired/stimulus/dist/stimulus.js");
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }



















function _classCallCheck(a, n) { if (!(a instanceof n)) throw new TypeError("Cannot call a class as a function"); }
function _defineProperties(e, r) { for (var t = 0; t < r.length; t++) { var o = r[t]; o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(e, _toPropertyKey(o.key), o); } }
function _createClass(e, r, t) { return r && _defineProperties(e.prototype, r), t && _defineProperties(e, t), Object.defineProperty(e, "prototype", { writable: !1 }), e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != _typeof(t) || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }
function _callSuper(t, o, e) { return o = _getPrototypeOf(o), _possibleConstructorReturn(t, _isNativeReflectConstruct() ? Reflect.construct(o, e || [], _getPrototypeOf(t).constructor) : o.apply(t, e)); }
function _possibleConstructorReturn(t, e) { if (e && ("object" == _typeof(e) || "function" == typeof e)) return e; if (void 0 !== e) throw new TypeError("Derived constructors may only return object or undefined"); return _assertThisInitialized(t); }
function _assertThisInitialized(e) { if (void 0 === e) throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); return e; }
function _isNativeReflectConstruct() { try { var t = !Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); } catch (t) {} return (_isNativeReflectConstruct = function _isNativeReflectConstruct() { return !!t; })(); }
function _getPrototypeOf(t) { return _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf.bind() : function (t) { return t.__proto__ || Object.getPrototypeOf(t); }, _getPrototypeOf(t); }
function _inherits(t, e) { if ("function" != typeof e && null !== e) throw new TypeError("Super expression must either be null or a function"); t.prototype = Object.create(e && e.prototype, { constructor: { value: t, writable: !0, configurable: !0 } }), Object.defineProperty(t, "prototype", { writable: !1 }), e && _setPrototypeOf(t, e); }
function _setPrototypeOf(t, e) { return _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function (t, e) { return t.__proto__ = e, t; }, _setPrototypeOf(t, e); }

var default_1 = /*#__PURE__*/function (_Controller) {
  function default_1() {
    _classCallCheck(this, default_1);
    return _callSuper(this, default_1, arguments);
  }
  _inherits(default_1, _Controller);
  return _createClass(default_1, [{
    key: "toggle",
    value: function toggle(event) {
      event.stopPropagation();
      var isOpen = !this.menuTarget.classList.contains('hidden');
      if (isOpen) {
        this.closeMenu();
      } else {
        this.openMenu();
      }
    }
  }, {
    key: "close",
    value: function close(event) {
      if (!this.element.contains(event.target)) {
        this.closeMenu();
      }
    }
    /**
     * BF-71: Escape schließt das Menü und gibt den Fokus zurück.
     *
     * `close` hängt an `click@window` und ist damit eine Maushandlung. Wer das Menü
     * per Tastatur öffnet, konnte es ohne Maus nicht wieder schließen — bei einem
     * Element mit `aria-haspopup` widerspricht das den ARIA Authoring Practices.
     */
  }, {
    key: "closeOnEscape",
    value: function closeOnEscape() {
      if (this.menuTarget.classList.contains('hidden')) {
        return;
      }
      this.closeMenu();
      this.buttonTarget.focus();
    }
  }, {
    key: "openMenu",
    value: function openMenu() {
      this.menuTarget.classList.remove('hidden');
      this.buttonTarget.setAttribute('aria-expanded', 'true');
      this.arrowTarget.classList.add('rotate-180');
    }
  }, {
    key: "closeMenu",
    value: function closeMenu() {
      this.menuTarget.classList.add('hidden');
      this.buttonTarget.setAttribute('aria-expanded', 'false');
      this.arrowTarget.classList.remove('rotate-180');
    }
  }]);
}(_hotwired_stimulus__WEBPACK_IMPORTED_MODULE_19__.Controller);
default_1.targets = ['menu', 'button', 'arrow'];
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (default_1);

/***/ },

/***/ "./node_modules/@symfony/stimulus-bridge/lazy-controller-loader.js!./assets/controllers/nav_dropdown_controller.ts"
/*!*************************************************************************************************************************!*\
  !*** ./node_modules/@symfony/stimulus-bridge/lazy-controller-loader.js!./assets/controllers/nav_dropdown_controller.ts ***!
  \*************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ _default)
/* harmony export */ });
/* harmony import */ var core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! core-js/modules/es.symbol.js */ "./node_modules/core-js/modules/es.symbol.js");
/* harmony import */ var core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! core-js/modules/es.symbol.description.js */ "./node_modules/core-js/modules/es.symbol.description.js");
/* harmony import */ var core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! core-js/modules/es.symbol.iterator.js */ "./node_modules/core-js/modules/es.symbol.iterator.js");
/* harmony import */ var core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var core_js_modules_es_symbol_to_primitive_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! core-js/modules/es.symbol.to-primitive.js */ "./node_modules/core-js/modules/es.symbol.to-primitive.js");
/* harmony import */ var core_js_modules_es_symbol_to_primitive_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_to_primitive_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var core_js_modules_es_error_cause_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! core-js/modules/es.error.cause.js */ "./node_modules/core-js/modules/es.error.cause.js");
/* harmony import */ var core_js_modules_es_error_cause_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_error_cause_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var core_js_modules_es_error_to_string_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! core-js/modules/es.error.to-string.js */ "./node_modules/core-js/modules/es.error.to-string.js");
/* harmony import */ var core_js_modules_es_error_to_string_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_error_to_string_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! core-js/modules/es.array.iterator.js */ "./node_modules/core-js/modules/es.array.iterator.js");
/* harmony import */ var core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var core_js_modules_es_date_to_primitive_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! core-js/modules/es.date.to-primitive.js */ "./node_modules/core-js/modules/es.date.to-primitive.js");
/* harmony import */ var core_js_modules_es_date_to_primitive_js__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_date_to_primitive_js__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var core_js_modules_es_function_bind_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! core-js/modules/es.function.bind.js */ "./node_modules/core-js/modules/es.function.bind.js");
/* harmony import */ var core_js_modules_es_function_bind_js__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_function_bind_js__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var core_js_modules_es_number_constructor_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! core-js/modules/es.number.constructor.js */ "./node_modules/core-js/modules/es.number.constructor.js");
/* harmony import */ var core_js_modules_es_number_constructor_js__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_number_constructor_js__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var core_js_modules_es_object_create_js__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! core-js/modules/es.object.create.js */ "./node_modules/core-js/modules/es.object.create.js");
/* harmony import */ var core_js_modules_es_object_create_js__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_create_js__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var core_js_modules_es_object_define_property_js__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! core-js/modules/es.object.define-property.js */ "./node_modules/core-js/modules/es.object.define-property.js");
/* harmony import */ var core_js_modules_es_object_define_property_js__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_define_property_js__WEBPACK_IMPORTED_MODULE_11__);
/* harmony import */ var core_js_modules_es_object_get_prototype_of_js__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! core-js/modules/es.object.get-prototype-of.js */ "./node_modules/core-js/modules/es.object.get-prototype-of.js");
/* harmony import */ var core_js_modules_es_object_get_prototype_of_js__WEBPACK_IMPORTED_MODULE_12___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_get_prototype_of_js__WEBPACK_IMPORTED_MODULE_12__);
/* harmony import */ var core_js_modules_es_object_proto_js__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! core-js/modules/es.object.proto.js */ "./node_modules/core-js/modules/es.object.proto.js");
/* harmony import */ var core_js_modules_es_object_proto_js__WEBPACK_IMPORTED_MODULE_13___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_proto_js__WEBPACK_IMPORTED_MODULE_13__);
/* harmony import */ var core_js_modules_es_object_set_prototype_of_js__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! core-js/modules/es.object.set-prototype-of.js */ "./node_modules/core-js/modules/es.object.set-prototype-of.js");
/* harmony import */ var core_js_modules_es_object_set_prototype_of_js__WEBPACK_IMPORTED_MODULE_14___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_set_prototype_of_js__WEBPACK_IMPORTED_MODULE_14__);
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! core-js/modules/es.object.to-string.js */ "./node_modules/core-js/modules/es.object.to-string.js");
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_15___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_15__);
/* harmony import */ var core_js_modules_es_reflect_construct_js__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! core-js/modules/es.reflect.construct.js */ "./node_modules/core-js/modules/es.reflect.construct.js");
/* harmony import */ var core_js_modules_es_reflect_construct_js__WEBPACK_IMPORTED_MODULE_16___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_reflect_construct_js__WEBPACK_IMPORTED_MODULE_16__);
/* harmony import */ var core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! core-js/modules/es.string.iterator.js */ "./node_modules/core-js/modules/es.string.iterator.js");
/* harmony import */ var core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_17___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_17__);
/* harmony import */ var core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! core-js/modules/web.dom-collections.iterator.js */ "./node_modules/core-js/modules/web.dom-collections.iterator.js");
/* harmony import */ var core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_18___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_18__);
/* harmony import */ var _hotwired_stimulus__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__(/*! @hotwired/stimulus */ "./node_modules/@hotwired/stimulus/dist/stimulus.js");
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }



















function _classCallCheck(a, n) { if (!(a instanceof n)) throw new TypeError("Cannot call a class as a function"); }
function _defineProperties(e, r) { for (var t = 0; t < r.length; t++) { var o = r[t]; o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(e, _toPropertyKey(o.key), o); } }
function _createClass(e, r, t) { return r && _defineProperties(e.prototype, r), t && _defineProperties(e, t), Object.defineProperty(e, "prototype", { writable: !1 }), e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != _typeof(t) || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }
function _callSuper(t, o, e) { return o = _getPrototypeOf(o), _possibleConstructorReturn(t, _isNativeReflectConstruct() ? Reflect.construct(o, e || [], _getPrototypeOf(t).constructor) : o.apply(t, e)); }
function _possibleConstructorReturn(t, e) { if (e && ("object" == _typeof(e) || "function" == typeof e)) return e; if (void 0 !== e) throw new TypeError("Derived constructors may only return object or undefined"); return _assertThisInitialized(t); }
function _assertThisInitialized(e) { if (void 0 === e) throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); return e; }
function _isNativeReflectConstruct() { try { var t = !Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); } catch (t) {} return (_isNativeReflectConstruct = function _isNativeReflectConstruct() { return !!t; })(); }
function _getPrototypeOf(t) { return _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf.bind() : function (t) { return t.__proto__ || Object.getPrototypeOf(t); }, _getPrototypeOf(t); }
function _inherits(t, e) { if ("function" != typeof e && null !== e) throw new TypeError("Super expression must either be null or a function"); t.prototype = Object.create(e && e.prototype, { constructor: { value: t, writable: !0, configurable: !0 } }), Object.defineProperty(t, "prototype", { writable: !1 }), e && _setPrototypeOf(t, e); }
function _setPrototypeOf(t, e) { return _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function (t, e) { return t.__proto__ = e, t; }, _setPrototypeOf(t, e); }

/**
 * Schließt ein <details>-Dropdown bei Escape oder Klick daneben.
 *
 * Rein zusätzlich: Das Aufklappen selbst erledigt <details> nativ – ohne
 * JavaScript bleibt das Menü also voll bedienbar, es schließt sich dann nur
 * nicht von allein. Deshalb wird hier auch kein aria-expanded gepflegt:
 * <details> meldet seinen Zustand bereits selbst an Screenreader.
 *
 * Die Handler sind gebundene Klassenfelder statt #private-Methoden: Babel kann
 * private Felder in der anonymen Controller-Klasse nicht übersetzen
 * ("A class name is required"), obwohl tsc sie akzeptiert.
 */
var _default = /*#__PURE__*/function (_Controller) {
  function _default() {
    var _this;
    _classCallCheck(this, _default);
    _this = _callSuper(this, _default, arguments);
    _this.onOutsideClick = function (event) {
      if (!_this.element.contains(event.target)) {
        _this.element.open = false;
      }
    };
    _this.onKeydown = function (event) {
      var _this$element$querySe;
      if (event.key !== 'Escape' || !_this.element.open) {
        return;
      }
      _this.element.open = false;
      // Fokus zurück auf den Auslöser, sonst landet er im Nirgendwo.
      (_this$element$querySe = _this.element.querySelector('summary')) === null || _this$element$querySe === void 0 || _this$element$querySe.focus();
    };
    return _this;
  }
  _inherits(_default, _Controller);
  return _createClass(_default, [{
    key: "connect",
    value: function connect() {
      document.addEventListener('click', this.onOutsideClick);
      document.addEventListener('keydown', this.onKeydown);
    }
  }, {
    key: "disconnect",
    value: function disconnect() {
      document.removeEventListener('click', this.onOutsideClick);
      document.removeEventListener('keydown', this.onKeydown);
    }
  }]);
}(_hotwired_stimulus__WEBPACK_IMPORTED_MODULE_19__.Controller);


/***/ },

/***/ "./node_modules/@symfony/stimulus-bridge/lazy-controller-loader.js!./assets/controllers/opening_hours_form_controller.ts"
/*!*******************************************************************************************************************************!*\
  !*** ./node_modules/@symfony/stimulus-bridge/lazy-controller-loader.js!./assets/controllers/opening_hours_form_controller.ts ***!
  \*******************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! core-js/modules/es.symbol.js */ "./node_modules/core-js/modules/es.symbol.js");
/* harmony import */ var core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! core-js/modules/es.symbol.description.js */ "./node_modules/core-js/modules/es.symbol.description.js");
/* harmony import */ var core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! core-js/modules/es.symbol.iterator.js */ "./node_modules/core-js/modules/es.symbol.iterator.js");
/* harmony import */ var core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var core_js_modules_es_symbol_to_primitive_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! core-js/modules/es.symbol.to-primitive.js */ "./node_modules/core-js/modules/es.symbol.to-primitive.js");
/* harmony import */ var core_js_modules_es_symbol_to_primitive_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_to_primitive_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var core_js_modules_es_error_cause_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! core-js/modules/es.error.cause.js */ "./node_modules/core-js/modules/es.error.cause.js");
/* harmony import */ var core_js_modules_es_error_cause_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_error_cause_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var core_js_modules_es_error_to_string_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! core-js/modules/es.error.to-string.js */ "./node_modules/core-js/modules/es.error.to-string.js");
/* harmony import */ var core_js_modules_es_error_to_string_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_error_to_string_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! core-js/modules/es.array.iterator.js */ "./node_modules/core-js/modules/es.array.iterator.js");
/* harmony import */ var core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var core_js_modules_es_date_to_primitive_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! core-js/modules/es.date.to-primitive.js */ "./node_modules/core-js/modules/es.date.to-primitive.js");
/* harmony import */ var core_js_modules_es_date_to_primitive_js__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_date_to_primitive_js__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var core_js_modules_es_function_bind_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! core-js/modules/es.function.bind.js */ "./node_modules/core-js/modules/es.function.bind.js");
/* harmony import */ var core_js_modules_es_function_bind_js__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_function_bind_js__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var core_js_modules_es_number_constructor_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! core-js/modules/es.number.constructor.js */ "./node_modules/core-js/modules/es.number.constructor.js");
/* harmony import */ var core_js_modules_es_number_constructor_js__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_number_constructor_js__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var core_js_modules_es_object_create_js__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! core-js/modules/es.object.create.js */ "./node_modules/core-js/modules/es.object.create.js");
/* harmony import */ var core_js_modules_es_object_create_js__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_create_js__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var core_js_modules_es_object_define_property_js__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! core-js/modules/es.object.define-property.js */ "./node_modules/core-js/modules/es.object.define-property.js");
/* harmony import */ var core_js_modules_es_object_define_property_js__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_define_property_js__WEBPACK_IMPORTED_MODULE_11__);
/* harmony import */ var core_js_modules_es_object_get_prototype_of_js__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! core-js/modules/es.object.get-prototype-of.js */ "./node_modules/core-js/modules/es.object.get-prototype-of.js");
/* harmony import */ var core_js_modules_es_object_get_prototype_of_js__WEBPACK_IMPORTED_MODULE_12___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_get_prototype_of_js__WEBPACK_IMPORTED_MODULE_12__);
/* harmony import */ var core_js_modules_es_object_proto_js__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! core-js/modules/es.object.proto.js */ "./node_modules/core-js/modules/es.object.proto.js");
/* harmony import */ var core_js_modules_es_object_proto_js__WEBPACK_IMPORTED_MODULE_13___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_proto_js__WEBPACK_IMPORTED_MODULE_13__);
/* harmony import */ var core_js_modules_es_object_set_prototype_of_js__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! core-js/modules/es.object.set-prototype-of.js */ "./node_modules/core-js/modules/es.object.set-prototype-of.js");
/* harmony import */ var core_js_modules_es_object_set_prototype_of_js__WEBPACK_IMPORTED_MODULE_14___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_set_prototype_of_js__WEBPACK_IMPORTED_MODULE_14__);
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! core-js/modules/es.object.to-string.js */ "./node_modules/core-js/modules/es.object.to-string.js");
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_15___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_15__);
/* harmony import */ var core_js_modules_es_reflect_construct_js__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! core-js/modules/es.reflect.construct.js */ "./node_modules/core-js/modules/es.reflect.construct.js");
/* harmony import */ var core_js_modules_es_reflect_construct_js__WEBPACK_IMPORTED_MODULE_16___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_reflect_construct_js__WEBPACK_IMPORTED_MODULE_16__);
/* harmony import */ var core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! core-js/modules/es.regexp.exec.js */ "./node_modules/core-js/modules/es.regexp.exec.js");
/* harmony import */ var core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_17___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_17__);
/* harmony import */ var core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! core-js/modules/es.string.iterator.js */ "./node_modules/core-js/modules/es.string.iterator.js");
/* harmony import */ var core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_18___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_18__);
/* harmony import */ var core_js_modules_es_string_replace_js__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__(/*! core-js/modules/es.string.replace.js */ "./node_modules/core-js/modules/es.string.replace.js");
/* harmony import */ var core_js_modules_es_string_replace_js__WEBPACK_IMPORTED_MODULE_19___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_string_replace_js__WEBPACK_IMPORTED_MODULE_19__);
/* harmony import */ var core_js_modules_es_weak_map_js__WEBPACK_IMPORTED_MODULE_20__ = __webpack_require__(/*! core-js/modules/es.weak-map.js */ "./node_modules/core-js/modules/es.weak-map.js");
/* harmony import */ var core_js_modules_es_weak_map_js__WEBPACK_IMPORTED_MODULE_20___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_weak_map_js__WEBPACK_IMPORTED_MODULE_20__);
/* harmony import */ var core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_21__ = __webpack_require__(/*! core-js/modules/web.dom-collections.iterator.js */ "./node_modules/core-js/modules/web.dom-collections.iterator.js");
/* harmony import */ var core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_21___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_21__);
/* harmony import */ var _hotwired_stimulus__WEBPACK_IMPORTED_MODULE_22__ = __webpack_require__(/*! @hotwired/stimulus */ "./node_modules/@hotwired/stimulus/dist/stimulus.js");
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _classCallCheck(a, n) { if (!(a instanceof n)) throw new TypeError("Cannot call a class as a function"); }
function _defineProperties(e, r) { for (var t = 0; t < r.length; t++) { var o = r[t]; o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(e, _toPropertyKey(o.key), o); } }
function _createClass(e, r, t) { return r && _defineProperties(e.prototype, r), t && _defineProperties(e, t), Object.defineProperty(e, "prototype", { writable: !1 }), e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != _typeof(t) || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }
function _callSuper(t, o, e) { return o = _getPrototypeOf(o), _possibleConstructorReturn(t, _isNativeReflectConstruct() ? Reflect.construct(o, e || [], _getPrototypeOf(t).constructor) : o.apply(t, e)); }
function _possibleConstructorReturn(t, e) { if (e && ("object" == _typeof(e) || "function" == typeof e)) return e; if (void 0 !== e) throw new TypeError("Derived constructors may only return object or undefined"); return _assertThisInitialized(t); }
function _assertThisInitialized(e) { if (void 0 === e) throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); return e; }
function _isNativeReflectConstruct() { try { var t = !Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); } catch (t) {} return (_isNativeReflectConstruct = function _isNativeReflectConstruct() { return !!t; })(); }
function _getPrototypeOf(t) { return _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf.bind() : function (t) { return t.__proto__ || Object.getPrototypeOf(t); }, _getPrototypeOf(t); }
function _inherits(t, e) { if ("function" != typeof e && null !== e) throw new TypeError("Super expression must either be null or a function"); t.prototype = Object.create(e && e.prototype, { constructor: { value: t, writable: !0, configurable: !0 } }), Object.defineProperty(t, "prototype", { writable: !1 }), e && _setPrototypeOf(t, e); }
function _setPrototypeOf(t, e) { return _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function (t, e) { return t.__proto__ = e, t; }, _setPrototypeOf(t, e); }






















var __classPrivateFieldSet = undefined && undefined.__classPrivateFieldSet || function (receiver, state, value, kind, f) {
  if (kind === "m") throw new TypeError("Private method is not writable");
  if (kind === "a" && !f) throw new TypeError("Private accessor was defined without a setter");
  if (typeof state === "function" ? receiver !== state || !f : !state.has(receiver)) throw new TypeError("Cannot write private member to an object whose class did not declare it");
  return kind === "a" ? f.call(receiver, value) : f ? f.value = value : state.set(receiver, value), value;
};
var __classPrivateFieldGet = undefined && undefined.__classPrivateFieldGet || function (receiver, state, kind, f) {
  if (kind === "a" && !f) throw new TypeError("Private accessor was defined without a getter");
  if (typeof state === "function" ? receiver !== state || !f : !state.has(receiver)) throw new TypeError("Cannot read private member from an object whose class did not declare it");
  return kind === "m" ? f : kind === "a" ? f.call(receiver) : f ? f.value : state.get(receiver);
};
var _default_1_index;

/*
 * Stimulus-Controller für die nach Wochentag gruppierten Öffnungszeiten-Slots.
 * Erlaubt das Hinzufügen mehrerer Zeitslots pro Tag (z. B. Mittag + Abend)
 * und das Entfernen einzelner Slots. Nutzt eine flache Symfony-CollectionType,
 * deshalb wird ein gemeinsamer, über alle Tage eindeutiger Index geführt.
 */
var default_1 = /*#__PURE__*/function (_Controller) {
  function default_1() {
    var _this;
    _classCallCheck(this, default_1);
    _this = _callSuper(this, default_1, arguments);
    _default_1_index.set(_this, void 0);
    return _this;
  }
  _inherits(default_1, _Controller);
  return _createClass(default_1, [{
    key: "connect",
    value: function connect() {
      __classPrivateFieldSet(this, _default_1_index, this.element.querySelectorAll('[data-opening-hours-form-target="slot"]').length, "f");
    }
  }, {
    key: "addSlot",
    value: function addSlot(event) {
      var _a;
      var button = event.currentTarget;
      var day = button.dataset.openingHoursFormDayParam;
      if (!day) {
        return;
      }
      var container = this.element.querySelector("[data-day=\"".concat(day, "\"]"));
      if (!container) {
        return;
      }
      var html = this.prototypeValue.replace(/__name__/g, String(__classPrivateFieldGet(this, _default_1_index, "f")));
      __classPrivateFieldSet(this, _default_1_index, (_a = __classPrivateFieldGet(this, _default_1_index, "f"), _a++, _a), "f");
      var wrapper = document.createElement('div');
      wrapper.classList.add('flex', 'items-center', 'gap-2');
      wrapper.setAttribute('data-opening-hours-form-target', 'slot');
      wrapper.innerHTML = html + '<button type="button" data-action="opening-hours-form#removeSlot" ' + 'class="text-red-500 hover:text-red-700 text-sm font-bold px-2 py-1 shrink-0 transition">' + '✕</button>';
      // Den versteckten dayOfWeek-Input des neuen Slots auf den Zieltag setzen.
      var dayInput = wrapper.querySelector('input[type="hidden"][name*="[dayOfWeek]"]');
      if (dayInput) {
        dayInput.value = day;
      }
      container.appendChild(wrapper);
    }
  }, {
    key: "removeSlot",
    value: function removeSlot(event) {
      var target = event.target;
      var slot = target.closest('[data-opening-hours-form-target="slot"]');
      if (slot) {
        slot.remove();
      }
    }
  }]);
}(_hotwired_stimulus__WEBPACK_IMPORTED_MODULE_22__.Controller);
_default_1_index = new WeakMap();
default_1.values = {
  prototype: String
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (default_1);

/***/ },

/***/ "./node_modules/@symfony/stimulus-bridge/lazy-controller-loader.js!./assets/controllers/organisation_type_controller.ts"
/*!******************************************************************************************************************************!*\
  !*** ./node_modules/@symfony/stimulus-bridge/lazy-controller-loader.js!./assets/controllers/organisation_type_controller.ts ***!
  \******************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! core-js/modules/es.symbol.js */ "./node_modules/core-js/modules/es.symbol.js");
/* harmony import */ var core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! core-js/modules/es.symbol.description.js */ "./node_modules/core-js/modules/es.symbol.description.js");
/* harmony import */ var core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! core-js/modules/es.symbol.iterator.js */ "./node_modules/core-js/modules/es.symbol.iterator.js");
/* harmony import */ var core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var core_js_modules_es_symbol_to_primitive_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! core-js/modules/es.symbol.to-primitive.js */ "./node_modules/core-js/modules/es.symbol.to-primitive.js");
/* harmony import */ var core_js_modules_es_symbol_to_primitive_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_to_primitive_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var core_js_modules_es_error_cause_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! core-js/modules/es.error.cause.js */ "./node_modules/core-js/modules/es.error.cause.js");
/* harmony import */ var core_js_modules_es_error_cause_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_error_cause_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var core_js_modules_es_error_to_string_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! core-js/modules/es.error.to-string.js */ "./node_modules/core-js/modules/es.error.to-string.js");
/* harmony import */ var core_js_modules_es_error_to_string_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_error_to_string_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var core_js_modules_es_array_find_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! core-js/modules/es.array.find.js */ "./node_modules/core-js/modules/es.array.find.js");
/* harmony import */ var core_js_modules_es_array_find_js__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_find_js__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var core_js_modules_es_array_for_each_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! core-js/modules/es.array.for-each.js */ "./node_modules/core-js/modules/es.array.for-each.js");
/* harmony import */ var core_js_modules_es_array_for_each_js__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_for_each_js__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! core-js/modules/es.array.iterator.js */ "./node_modules/core-js/modules/es.array.iterator.js");
/* harmony import */ var core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var core_js_modules_es_date_to_primitive_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! core-js/modules/es.date.to-primitive.js */ "./node_modules/core-js/modules/es.date.to-primitive.js");
/* harmony import */ var core_js_modules_es_date_to_primitive_js__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_date_to_primitive_js__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var core_js_modules_es_function_bind_js__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! core-js/modules/es.function.bind.js */ "./node_modules/core-js/modules/es.function.bind.js");
/* harmony import */ var core_js_modules_es_function_bind_js__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_function_bind_js__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var core_js_modules_es_number_constructor_js__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! core-js/modules/es.number.constructor.js */ "./node_modules/core-js/modules/es.number.constructor.js");
/* harmony import */ var core_js_modules_es_number_constructor_js__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_number_constructor_js__WEBPACK_IMPORTED_MODULE_11__);
/* harmony import */ var core_js_modules_es_object_create_js__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! core-js/modules/es.object.create.js */ "./node_modules/core-js/modules/es.object.create.js");
/* harmony import */ var core_js_modules_es_object_create_js__WEBPACK_IMPORTED_MODULE_12___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_create_js__WEBPACK_IMPORTED_MODULE_12__);
/* harmony import */ var core_js_modules_es_object_define_property_js__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! core-js/modules/es.object.define-property.js */ "./node_modules/core-js/modules/es.object.define-property.js");
/* harmony import */ var core_js_modules_es_object_define_property_js__WEBPACK_IMPORTED_MODULE_13___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_define_property_js__WEBPACK_IMPORTED_MODULE_13__);
/* harmony import */ var core_js_modules_es_object_get_prototype_of_js__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! core-js/modules/es.object.get-prototype-of.js */ "./node_modules/core-js/modules/es.object.get-prototype-of.js");
/* harmony import */ var core_js_modules_es_object_get_prototype_of_js__WEBPACK_IMPORTED_MODULE_14___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_get_prototype_of_js__WEBPACK_IMPORTED_MODULE_14__);
/* harmony import */ var core_js_modules_es_object_proto_js__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! core-js/modules/es.object.proto.js */ "./node_modules/core-js/modules/es.object.proto.js");
/* harmony import */ var core_js_modules_es_object_proto_js__WEBPACK_IMPORTED_MODULE_15___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_proto_js__WEBPACK_IMPORTED_MODULE_15__);
/* harmony import */ var core_js_modules_es_object_set_prototype_of_js__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! core-js/modules/es.object.set-prototype-of.js */ "./node_modules/core-js/modules/es.object.set-prototype-of.js");
/* harmony import */ var core_js_modules_es_object_set_prototype_of_js__WEBPACK_IMPORTED_MODULE_16___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_set_prototype_of_js__WEBPACK_IMPORTED_MODULE_16__);
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! core-js/modules/es.object.to-string.js */ "./node_modules/core-js/modules/es.object.to-string.js");
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_17___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_17__);
/* harmony import */ var core_js_modules_es_reflect_construct_js__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! core-js/modules/es.reflect.construct.js */ "./node_modules/core-js/modules/es.reflect.construct.js");
/* harmony import */ var core_js_modules_es_reflect_construct_js__WEBPACK_IMPORTED_MODULE_18___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_reflect_construct_js__WEBPACK_IMPORTED_MODULE_18__);
/* harmony import */ var core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__(/*! core-js/modules/es.regexp.exec.js */ "./node_modules/core-js/modules/es.regexp.exec.js");
/* harmony import */ var core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_19___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_19__);
/* harmony import */ var core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_20__ = __webpack_require__(/*! core-js/modules/es.string.iterator.js */ "./node_modules/core-js/modules/es.string.iterator.js");
/* harmony import */ var core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_20___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_20__);
/* harmony import */ var core_js_modules_es_string_replace_js__WEBPACK_IMPORTED_MODULE_21__ = __webpack_require__(/*! core-js/modules/es.string.replace.js */ "./node_modules/core-js/modules/es.string.replace.js");
/* harmony import */ var core_js_modules_es_string_replace_js__WEBPACK_IMPORTED_MODULE_21___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_string_replace_js__WEBPACK_IMPORTED_MODULE_21__);
/* harmony import */ var core_js_modules_esnext_iterator_constructor_js__WEBPACK_IMPORTED_MODULE_22__ = __webpack_require__(/*! core-js/modules/esnext.iterator.constructor.js */ "./node_modules/core-js/modules/esnext.iterator.constructor.js");
/* harmony import */ var core_js_modules_esnext_iterator_constructor_js__WEBPACK_IMPORTED_MODULE_22___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_esnext_iterator_constructor_js__WEBPACK_IMPORTED_MODULE_22__);
/* harmony import */ var core_js_modules_esnext_iterator_find_js__WEBPACK_IMPORTED_MODULE_23__ = __webpack_require__(/*! core-js/modules/esnext.iterator.find.js */ "./node_modules/core-js/modules/esnext.iterator.find.js");
/* harmony import */ var core_js_modules_esnext_iterator_find_js__WEBPACK_IMPORTED_MODULE_23___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_esnext_iterator_find_js__WEBPACK_IMPORTED_MODULE_23__);
/* harmony import */ var core_js_modules_esnext_iterator_for_each_js__WEBPACK_IMPORTED_MODULE_24__ = __webpack_require__(/*! core-js/modules/esnext.iterator.for-each.js */ "./node_modules/core-js/modules/esnext.iterator.for-each.js");
/* harmony import */ var core_js_modules_esnext_iterator_for_each_js__WEBPACK_IMPORTED_MODULE_24___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_esnext_iterator_for_each_js__WEBPACK_IMPORTED_MODULE_24__);
/* harmony import */ var core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_25__ = __webpack_require__(/*! core-js/modules/web.dom-collections.for-each.js */ "./node_modules/core-js/modules/web.dom-collections.for-each.js");
/* harmony import */ var core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_25___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_25__);
/* harmony import */ var core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_26__ = __webpack_require__(/*! core-js/modules/web.dom-collections.iterator.js */ "./node_modules/core-js/modules/web.dom-collections.iterator.js");
/* harmony import */ var core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_26___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_26__);
/* harmony import */ var core_js_modules_web_timers_js__WEBPACK_IMPORTED_MODULE_27__ = __webpack_require__(/*! core-js/modules/web.timers.js */ "./node_modules/core-js/modules/web.timers.js");
/* harmony import */ var core_js_modules_web_timers_js__WEBPACK_IMPORTED_MODULE_27___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_timers_js__WEBPACK_IMPORTED_MODULE_27__);
/* harmony import */ var _hotwired_stimulus__WEBPACK_IMPORTED_MODULE_28__ = __webpack_require__(/*! @hotwired/stimulus */ "./node_modules/@hotwired/stimulus/dist/stimulus.js");
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }




























function _classCallCheck(a, n) { if (!(a instanceof n)) throw new TypeError("Cannot call a class as a function"); }
function _defineProperties(e, r) { for (var t = 0; t < r.length; t++) { var o = r[t]; o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(e, _toPropertyKey(o.key), o); } }
function _createClass(e, r, t) { return r && _defineProperties(e.prototype, r), t && _defineProperties(e, t), Object.defineProperty(e, "prototype", { writable: !1 }), e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != _typeof(t) || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }
function _callSuper(t, o, e) { return o = _getPrototypeOf(o), _possibleConstructorReturn(t, _isNativeReflectConstruct() ? Reflect.construct(o, e || [], _getPrototypeOf(t).constructor) : o.apply(t, e)); }
function _possibleConstructorReturn(t, e) { if (e && ("object" == _typeof(e) || "function" == typeof e)) return e; if (void 0 !== e) throw new TypeError("Derived constructors may only return object or undefined"); return _assertThisInitialized(t); }
function _assertThisInitialized(e) { if (void 0 === e) throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); return e; }
function _isNativeReflectConstruct() { try { var t = !Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); } catch (t) {} return (_isNativeReflectConstruct = function _isNativeReflectConstruct() { return !!t; })(); }
function _getPrototypeOf(t) { return _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf.bind() : function (t) { return t.__proto__ || Object.getPrototypeOf(t); }, _getPrototypeOf(t); }
function _inherits(t, e) { if ("function" != typeof e && null !== e) throw new TypeError("Super expression must either be null or a function"); t.prototype = Object.create(e && e.prototype, { constructor: { value: t, writable: !0, configurable: !0 } }), Object.defineProperty(t, "prototype", { writable: !1 }), e && _setPrototypeOf(t, e); }
function _setPrototypeOf(t, e) { return _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function (t, e) { return t.__proto__ = e, t; }, _setPrototypeOf(t, e); }

/**
 * Blendet die typspezifischen Formularblöcke passend zum gewählten
 * Organisationstyp ein und aus.
 *
 * Rein zusätzlich: Ohne JavaScript rendert der FormType alle drei Blöcke, und
 * PRE_SUBMIT verwirft serverseitig die Felder der nicht gewählten Typen. Der
 * Controller ändert also nur, was sichtbar ist – nie, was gültig ist.
 *
 * Der Wechsel wird in einer Live-Region angesagt, sonst bekommen
 * Screenreader-Nutzer nicht mit, dass sich das Formular verändert hat.
 */
var default_1 = /*#__PURE__*/function (_Controller) {
  function default_1() {
    _classCallCheck(this, default_1);
    return _callSuper(this, default_1, arguments);
  }
  _inherits(default_1, _Controller);
  return _createClass(default_1, [{
    key: "connect",
    value: function connect() {
      this.update(false);
    }
  }, {
    key: "change",
    value: function change() {
      this.update(true);
    }
  }, {
    key: "update",
    value: function update(announce) {
      var selected = this.selectedType();
      this.blockTargets.forEach(function (block) {
        var matches = block.dataset.type === selected;
        block.hidden = !matches;
        // Felder des nicht gewählten Typs aus der Tab-Reihenfolge nehmen –
        // `hidden` allein genügt bei manchen Kombinationen nicht.
        block.querySelectorAll('input, select, textarea').forEach(function (field) {
          field.disabled = !matches;
        });
      });
      if (announce && selected) {
        this.announce(selected);
      }
    }
  }, {
    key: "selectedType",
    value: function selectedType() {
      var checked = this.element.querySelector('input[type="radio"]:checked');
      return checked ? checked.value : null;
    }
  }, {
    key: "announce",
    value: function announce(type) {
      var _block$dataset$label,
        _this = this;
      if (!this.hasAnnouncerTarget) {
        return;
      }
      var block = this.blockTargets.find(function (b) {
        return b.dataset.type === type;
      });
      var label = (_block$dataset$label = block === null || block === void 0 ? void 0 : block.dataset.label) !== null && _block$dataset$label !== void 0 ? _block$dataset$label : '';
      // Kurz leeren, damit auch eine wiederholte Auswahl neu vorgelesen wird.
      this.announcerTarget.textContent = '';
      window.setTimeout(function () {
        _this.announcerTarget.textContent = _this.announcementValue.replace('%type%', label);
      }, 50);
    }
  }]);
}(_hotwired_stimulus__WEBPACK_IMPORTED_MODULE_28__.Controller);
default_1.targets = ['block', 'announcer'];
default_1.values = {
  announcement: String
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (default_1);

/***/ },

/***/ "./node_modules/@symfony/stimulus-bridge/lazy-controller-loader.js!./assets/controllers/passkey_ui_controller.ts"
/*!***********************************************************************************************************************!*\
  !*** ./node_modules/@symfony/stimulus-bridge/lazy-controller-loader.js!./assets/controllers/passkey_ui_controller.ts ***!
  \***********************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! core-js/modules/es.symbol.js */ "./node_modules/core-js/modules/es.symbol.js");
/* harmony import */ var core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! core-js/modules/es.symbol.description.js */ "./node_modules/core-js/modules/es.symbol.description.js");
/* harmony import */ var core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! core-js/modules/es.symbol.iterator.js */ "./node_modules/core-js/modules/es.symbol.iterator.js");
/* harmony import */ var core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var core_js_modules_es_symbol_to_primitive_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! core-js/modules/es.symbol.to-primitive.js */ "./node_modules/core-js/modules/es.symbol.to-primitive.js");
/* harmony import */ var core_js_modules_es_symbol_to_primitive_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_to_primitive_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var core_js_modules_es_error_cause_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! core-js/modules/es.error.cause.js */ "./node_modules/core-js/modules/es.error.cause.js");
/* harmony import */ var core_js_modules_es_error_cause_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_error_cause_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var core_js_modules_es_error_to_string_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! core-js/modules/es.error.to-string.js */ "./node_modules/core-js/modules/es.error.to-string.js");
/* harmony import */ var core_js_modules_es_error_to_string_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_error_to_string_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! core-js/modules/es.array.iterator.js */ "./node_modules/core-js/modules/es.array.iterator.js");
/* harmony import */ var core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var core_js_modules_es_date_to_primitive_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! core-js/modules/es.date.to-primitive.js */ "./node_modules/core-js/modules/es.date.to-primitive.js");
/* harmony import */ var core_js_modules_es_date_to_primitive_js__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_date_to_primitive_js__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var core_js_modules_es_function_bind_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! core-js/modules/es.function.bind.js */ "./node_modules/core-js/modules/es.function.bind.js");
/* harmony import */ var core_js_modules_es_function_bind_js__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_function_bind_js__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var core_js_modules_es_number_constructor_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! core-js/modules/es.number.constructor.js */ "./node_modules/core-js/modules/es.number.constructor.js");
/* harmony import */ var core_js_modules_es_number_constructor_js__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_number_constructor_js__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var core_js_modules_es_object_create_js__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! core-js/modules/es.object.create.js */ "./node_modules/core-js/modules/es.object.create.js");
/* harmony import */ var core_js_modules_es_object_create_js__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_create_js__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var core_js_modules_es_object_define_property_js__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! core-js/modules/es.object.define-property.js */ "./node_modules/core-js/modules/es.object.define-property.js");
/* harmony import */ var core_js_modules_es_object_define_property_js__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_define_property_js__WEBPACK_IMPORTED_MODULE_11__);
/* harmony import */ var core_js_modules_es_object_get_prototype_of_js__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! core-js/modules/es.object.get-prototype-of.js */ "./node_modules/core-js/modules/es.object.get-prototype-of.js");
/* harmony import */ var core_js_modules_es_object_get_prototype_of_js__WEBPACK_IMPORTED_MODULE_12___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_get_prototype_of_js__WEBPACK_IMPORTED_MODULE_12__);
/* harmony import */ var core_js_modules_es_object_proto_js__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! core-js/modules/es.object.proto.js */ "./node_modules/core-js/modules/es.object.proto.js");
/* harmony import */ var core_js_modules_es_object_proto_js__WEBPACK_IMPORTED_MODULE_13___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_proto_js__WEBPACK_IMPORTED_MODULE_13__);
/* harmony import */ var core_js_modules_es_object_set_prototype_of_js__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! core-js/modules/es.object.set-prototype-of.js */ "./node_modules/core-js/modules/es.object.set-prototype-of.js");
/* harmony import */ var core_js_modules_es_object_set_prototype_of_js__WEBPACK_IMPORTED_MODULE_14___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_set_prototype_of_js__WEBPACK_IMPORTED_MODULE_14__);
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! core-js/modules/es.object.to-string.js */ "./node_modules/core-js/modules/es.object.to-string.js");
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_15___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_15__);
/* harmony import */ var core_js_modules_es_reflect_construct_js__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! core-js/modules/es.reflect.construct.js */ "./node_modules/core-js/modules/es.reflect.construct.js");
/* harmony import */ var core_js_modules_es_reflect_construct_js__WEBPACK_IMPORTED_MODULE_16___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_reflect_construct_js__WEBPACK_IMPORTED_MODULE_16__);
/* harmony import */ var core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! core-js/modules/es.string.iterator.js */ "./node_modules/core-js/modules/es.string.iterator.js");
/* harmony import */ var core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_17___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_17__);
/* harmony import */ var core_js_modules_es_weak_set_js__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! core-js/modules/es.weak-set.js */ "./node_modules/core-js/modules/es.weak-set.js");
/* harmony import */ var core_js_modules_es_weak_set_js__WEBPACK_IMPORTED_MODULE_18___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_weak_set_js__WEBPACK_IMPORTED_MODULE_18__);
/* harmony import */ var core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__(/*! core-js/modules/web.dom-collections.iterator.js */ "./node_modules/core-js/modules/web.dom-collections.iterator.js");
/* harmony import */ var core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_19___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_19__);
/* harmony import */ var _hotwired_stimulus__WEBPACK_IMPORTED_MODULE_20__ = __webpack_require__(/*! @hotwired/stimulus */ "./node_modules/@hotwired/stimulus/dist/stimulus.js");
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _classCallCheck(a, n) { if (!(a instanceof n)) throw new TypeError("Cannot call a class as a function"); }
function _defineProperties(e, r) { for (var t = 0; t < r.length; t++) { var o = r[t]; o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(e, _toPropertyKey(o.key), o); } }
function _createClass(e, r, t) { return r && _defineProperties(e.prototype, r), t && _defineProperties(e, t), Object.defineProperty(e, "prototype", { writable: !1 }), e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != _typeof(t) || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }
function _callSuper(t, o, e) { return o = _getPrototypeOf(o), _possibleConstructorReturn(t, _isNativeReflectConstruct() ? Reflect.construct(o, e || [], _getPrototypeOf(t).constructor) : o.apply(t, e)); }
function _possibleConstructorReturn(t, e) { if (e && ("object" == _typeof(e) || "function" == typeof e)) return e; if (void 0 !== e) throw new TypeError("Derived constructors may only return object or undefined"); return _assertThisInitialized(t); }
function _assertThisInitialized(e) { if (void 0 === e) throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); return e; }
function _isNativeReflectConstruct() { try { var t = !Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); } catch (t) {} return (_isNativeReflectConstruct = function _isNativeReflectConstruct() { return !!t; })(); }
function _getPrototypeOf(t) { return _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf.bind() : function (t) { return t.__proto__ || Object.getPrototypeOf(t); }, _getPrototypeOf(t); }
function _inherits(t, e) { if ("function" != typeof e && null !== e) throw new TypeError("Super expression must either be null or a function"); t.prototype = Object.create(e && e.prototype, { constructor: { value: t, writable: !0, configurable: !0 } }), Object.defineProperty(t, "prototype", { writable: !1 }), e && _setPrototypeOf(t, e); }
function _setPrototypeOf(t, e) { return _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function (t, e) { return t.__proto__ = e, t; }, _setPrototypeOf(t, e); }




















var __classPrivateFieldGet = undefined && undefined.__classPrivateFieldGet || function (receiver, state, kind, f) {
  if (kind === "a" && !f) throw new TypeError("Private accessor was defined without a getter");
  if (typeof state === "function" ? receiver !== state || !f : !state.has(receiver)) throw new TypeError("Cannot read private member from an object whose class did not declare it");
  return kind === "m" ? f : kind === "a" ? f.call(receiver) : f ? f.value : state.get(receiver);
};
var _default_1_instances, _default_1_browserSupportsPasskeys, _default_1_reset, _default_1_showMessage, _default_1_clearMessage;

/**
 * Sichtbarkeit, Ladezustand und verständliche Fehlermeldungen rund um Passkeys.
 *
 * Den WebAuthn-Ablauf selbst übernehmen die beiden Controller aus
 * `@web-auth/webauthn-stimulus` (registriert in stimulus_bootstrap.ts als
 * `passkey-auth` und `passkey-register`). Die melden ihren Fortschritt über
 * aufsteigende CustomEvents – dieser Controller hört darauf und macht daraus
 * das, was das Fremdpaket nicht liefern kann: übersetzten Text und einen
 * Knopf, der erst erscheint, wenn der Browser überhaupt Passkeys beherrscht.
 *
 * Die Meldungen kommen als Values aus dem Template, weil die Übersetzung dort
 * hingehört und nicht in eine JavaScript-Datei.
 */
var default_1 = /*#__PURE__*/function (_Controller) {
  function default_1() {
    var _this;
    _classCallCheck(this, default_1);
    _this = _callSuper(this, default_1, arguments);
    _default_1_instances.add(_this);
    _this.idleLabel = '';
    return _this;
  }
  _inherits(default_1, _Controller);
  return _createClass(default_1, [{
    key: "connect",
    value: function connect() {
      // Ohne WebAuthn im Browser bleibt der Knopf verborgen: Ein Angebot, das
      // beim Antippen nur eine Fehlermeldung liefert, ist schlechter als
      // keines. Der Passwort-Login steht ohnehin daneben.
      if (this.hasPanelTarget && __classPrivateFieldGet(this, _default_1_instances, "m", _default_1_browserSupportsPasskeys).call(this)) {
        this.panelTarget.classList.remove('hidden');
      }
      if (this.hasButtonTarget) {
        var _this$buttonTarget$te;
        this.idleLabel = (_this$buttonTarget$te = this.buttonTarget.textContent) !== null && _this$buttonTarget$te !== void 0 ? _this$buttonTarget$te : '';
      }
    }
    // Der Ablauf hat begonnen – ab hier wartet der Browser auf Face ID, Touch ID oder PIN.
  }, {
    key: "start",
    value: function start() {
      __classPrivateFieldGet(this, _default_1_instances, "m", _default_1_clearMessage).call(this);
      if (this.hasButtonTarget) {
        this.buttonTarget.disabled = true;
        this.buttonTarget.setAttribute('aria-busy', 'true');
        if (this.busyValue !== '') {
          this.buttonTarget.textContent = this.busyValue;
        }
      }
    }
  }, {
    key: "unsupported",
    value: function unsupported() {
      __classPrivateFieldGet(this, _default_1_instances, "m", _default_1_reset).call(this);
      __classPrivateFieldGet(this, _default_1_instances, "m", _default_1_showMessage).call(this, this.unsupportedValue);
    }
    /**
     * Fehler aus dem Ceremony-Teil (navigator.credentials).
     */
  }, {
    key: "ceremonyError",
    value: function ceremonyError(event) {
      var _event$detail;
      __classPrivateFieldGet(this, _default_1_instances, "m", _default_1_reset).call(this);
      var code = (_event$detail = event.detail) === null || _event$detail === void 0 ? void 0 : _event$detail.code;
      // Abbruch durch den Nutzer oder abgelaufenes Zeitfenster. Das ist kein
      // Fehler, sondern eine Entscheidung – dafür gibt es keine Meldung.
      if (code === 'ERROR_CEREMONY_ABORTED') {
        return;
      }
      if (code === 'ERROR_AUTHENTICATOR_PREVIOUSLY_REGISTERED') {
        __classPrivateFieldGet(this, _default_1_instances, "m", _default_1_showMessage).call(this, this.existsValue);
        return;
      }
      // Die Domain passt nicht zur konfigurierten relying party id. Betrifft
      // nie den Nutzer, sondern immer die Einrichtung – deshalb ein eigener
      // Text statt der allgemeinen Fehlermeldung.
      if (code === 'ERROR_INVALID_DOMAIN' || code === 'ERROR_INVALID_RP_ID') {
        __classPrivateFieldGet(this, _default_1_instances, "m", _default_1_showMessage).call(this, this.configValue);
        return;
      }
      __classPrivateFieldGet(this, _default_1_instances, "m", _default_1_showMessage).call(this, this.failedValue);
    }
    /**
     * Fehler auf dem Weg zum oder vom Server.
     */
  }, {
    key: "serverError",
    value: function serverError() {
      __classPrivateFieldGet(this, _default_1_instances, "m", _default_1_reset).call(this);
      __classPrivateFieldGet(this, _default_1_instances, "m", _default_1_showMessage).call(this, this.serverValue);
    }
  }]);
}(_hotwired_stimulus__WEBPACK_IMPORTED_MODULE_20__.Controller);
_default_1_instances = new WeakSet(), _default_1_browserSupportsPasskeys = function _default_1_browserSupportsPasskeys() {
  return typeof window.PublicKeyCredential === 'function';
}, _default_1_reset = function _default_1_reset() {
  if (this.hasButtonTarget) {
    this.buttonTarget.disabled = false;
    this.buttonTarget.removeAttribute('aria-busy');
    this.buttonTarget.textContent = this.idleLabel;
  }
}, _default_1_showMessage = function _default_1_showMessage(text) {
  if (this.hasMessageTarget && text !== '') {
    // Erst sichtbar machen, dann beschriften: Ein role="alert" meldet
    // nur Änderungen, die in einem bereits dargestellten Bereich
    // passieren. Andersherum verschlucken manche Screenreader die
    // Ansage.
    this.messageTarget.classList.remove('hidden');
    this.messageTarget.textContent = text;
  }
}, _default_1_clearMessage = function _default_1_clearMessage() {
  if (this.hasMessageTarget) {
    this.messageTarget.textContent = '';
    this.messageTarget.classList.add('hidden');
  }
};
default_1.targets = ['panel', 'button', 'message'];
default_1.values = {
  unsupported: String,
  failed: String,
  server: String,
  exists: String,
  config: String,
  busy: String
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (default_1);

/***/ },

/***/ "./node_modules/@symfony/stimulus-bridge/lazy-controller-loader.js!./assets/controllers/suggestion_wizard_controller.ts"
/*!******************************************************************************************************************************!*\
  !*** ./node_modules/@symfony/stimulus-bridge/lazy-controller-loader.js!./assets/controllers/suggestion_wizard_controller.ts ***!
  \******************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! core-js/modules/es.symbol.js */ "./node_modules/core-js/modules/es.symbol.js");
/* harmony import */ var core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! core-js/modules/es.symbol.description.js */ "./node_modules/core-js/modules/es.symbol.description.js");
/* harmony import */ var core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! core-js/modules/es.symbol.iterator.js */ "./node_modules/core-js/modules/es.symbol.iterator.js");
/* harmony import */ var core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var core_js_modules_es_symbol_to_primitive_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! core-js/modules/es.symbol.to-primitive.js */ "./node_modules/core-js/modules/es.symbol.to-primitive.js");
/* harmony import */ var core_js_modules_es_symbol_to_primitive_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_to_primitive_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var core_js_modules_es_error_cause_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! core-js/modules/es.error.cause.js */ "./node_modules/core-js/modules/es.error.cause.js");
/* harmony import */ var core_js_modules_es_error_cause_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_error_cause_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var core_js_modules_es_error_to_string_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! core-js/modules/es.error.to-string.js */ "./node_modules/core-js/modules/es.error.to-string.js");
/* harmony import */ var core_js_modules_es_error_to_string_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_error_to_string_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var core_js_modules_es_array_find_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! core-js/modules/es.array.find.js */ "./node_modules/core-js/modules/es.array.find.js");
/* harmony import */ var core_js_modules_es_array_find_js__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_find_js__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var core_js_modules_es_array_for_each_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! core-js/modules/es.array.for-each.js */ "./node_modules/core-js/modules/es.array.for-each.js");
/* harmony import */ var core_js_modules_es_array_for_each_js__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_for_each_js__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var core_js_modules_es_array_from_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! core-js/modules/es.array.from.js */ "./node_modules/core-js/modules/es.array.from.js");
/* harmony import */ var core_js_modules_es_array_from_js__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_from_js__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! core-js/modules/es.array.iterator.js */ "./node_modules/core-js/modules/es.array.iterator.js");
/* harmony import */ var core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var core_js_modules_es_date_to_primitive_js__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! core-js/modules/es.date.to-primitive.js */ "./node_modules/core-js/modules/es.date.to-primitive.js");
/* harmony import */ var core_js_modules_es_date_to_primitive_js__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_date_to_primitive_js__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var core_js_modules_es_function_bind_js__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! core-js/modules/es.function.bind.js */ "./node_modules/core-js/modules/es.function.bind.js");
/* harmony import */ var core_js_modules_es_function_bind_js__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_function_bind_js__WEBPACK_IMPORTED_MODULE_11__);
/* harmony import */ var core_js_modules_es_number_constructor_js__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! core-js/modules/es.number.constructor.js */ "./node_modules/core-js/modules/es.number.constructor.js");
/* harmony import */ var core_js_modules_es_number_constructor_js__WEBPACK_IMPORTED_MODULE_12___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_number_constructor_js__WEBPACK_IMPORTED_MODULE_12__);
/* harmony import */ var core_js_modules_es_object_create_js__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! core-js/modules/es.object.create.js */ "./node_modules/core-js/modules/es.object.create.js");
/* harmony import */ var core_js_modules_es_object_create_js__WEBPACK_IMPORTED_MODULE_13___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_create_js__WEBPACK_IMPORTED_MODULE_13__);
/* harmony import */ var core_js_modules_es_object_define_property_js__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! core-js/modules/es.object.define-property.js */ "./node_modules/core-js/modules/es.object.define-property.js");
/* harmony import */ var core_js_modules_es_object_define_property_js__WEBPACK_IMPORTED_MODULE_14___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_define_property_js__WEBPACK_IMPORTED_MODULE_14__);
/* harmony import */ var core_js_modules_es_object_get_prototype_of_js__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! core-js/modules/es.object.get-prototype-of.js */ "./node_modules/core-js/modules/es.object.get-prototype-of.js");
/* harmony import */ var core_js_modules_es_object_get_prototype_of_js__WEBPACK_IMPORTED_MODULE_15___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_get_prototype_of_js__WEBPACK_IMPORTED_MODULE_15__);
/* harmony import */ var core_js_modules_es_object_proto_js__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! core-js/modules/es.object.proto.js */ "./node_modules/core-js/modules/es.object.proto.js");
/* harmony import */ var core_js_modules_es_object_proto_js__WEBPACK_IMPORTED_MODULE_16___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_proto_js__WEBPACK_IMPORTED_MODULE_16__);
/* harmony import */ var core_js_modules_es_object_set_prototype_of_js__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! core-js/modules/es.object.set-prototype-of.js */ "./node_modules/core-js/modules/es.object.set-prototype-of.js");
/* harmony import */ var core_js_modules_es_object_set_prototype_of_js__WEBPACK_IMPORTED_MODULE_17___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_set_prototype_of_js__WEBPACK_IMPORTED_MODULE_17__);
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! core-js/modules/es.object.to-string.js */ "./node_modules/core-js/modules/es.object.to-string.js");
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_18___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_18__);
/* harmony import */ var core_js_modules_es_parse_int_js__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__(/*! core-js/modules/es.parse-int.js */ "./node_modules/core-js/modules/es.parse-int.js");
/* harmony import */ var core_js_modules_es_parse_int_js__WEBPACK_IMPORTED_MODULE_19___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_parse_int_js__WEBPACK_IMPORTED_MODULE_19__);
/* harmony import */ var core_js_modules_es_reflect_construct_js__WEBPACK_IMPORTED_MODULE_20__ = __webpack_require__(/*! core-js/modules/es.reflect.construct.js */ "./node_modules/core-js/modules/es.reflect.construct.js");
/* harmony import */ var core_js_modules_es_reflect_construct_js__WEBPACK_IMPORTED_MODULE_20___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_reflect_construct_js__WEBPACK_IMPORTED_MODULE_20__);
/* harmony import */ var core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_21__ = __webpack_require__(/*! core-js/modules/es.regexp.exec.js */ "./node_modules/core-js/modules/es.regexp.exec.js");
/* harmony import */ var core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_21___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_21__);
/* harmony import */ var core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_22__ = __webpack_require__(/*! core-js/modules/es.string.iterator.js */ "./node_modules/core-js/modules/es.string.iterator.js");
/* harmony import */ var core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_22___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_22__);
/* harmony import */ var core_js_modules_es_string_replace_js__WEBPACK_IMPORTED_MODULE_23__ = __webpack_require__(/*! core-js/modules/es.string.replace.js */ "./node_modules/core-js/modules/es.string.replace.js");
/* harmony import */ var core_js_modules_es_string_replace_js__WEBPACK_IMPORTED_MODULE_23___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_string_replace_js__WEBPACK_IMPORTED_MODULE_23__);
/* harmony import */ var core_js_modules_es_string_trim_js__WEBPACK_IMPORTED_MODULE_24__ = __webpack_require__(/*! core-js/modules/es.string.trim.js */ "./node_modules/core-js/modules/es.string.trim.js");
/* harmony import */ var core_js_modules_es_string_trim_js__WEBPACK_IMPORTED_MODULE_24___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_string_trim_js__WEBPACK_IMPORTED_MODULE_24__);
/* harmony import */ var core_js_modules_esnext_iterator_constructor_js__WEBPACK_IMPORTED_MODULE_25__ = __webpack_require__(/*! core-js/modules/esnext.iterator.constructor.js */ "./node_modules/core-js/modules/esnext.iterator.constructor.js");
/* harmony import */ var core_js_modules_esnext_iterator_constructor_js__WEBPACK_IMPORTED_MODULE_25___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_esnext_iterator_constructor_js__WEBPACK_IMPORTED_MODULE_25__);
/* harmony import */ var core_js_modules_esnext_iterator_find_js__WEBPACK_IMPORTED_MODULE_26__ = __webpack_require__(/*! core-js/modules/esnext.iterator.find.js */ "./node_modules/core-js/modules/esnext.iterator.find.js");
/* harmony import */ var core_js_modules_esnext_iterator_find_js__WEBPACK_IMPORTED_MODULE_26___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_esnext_iterator_find_js__WEBPACK_IMPORTED_MODULE_26__);
/* harmony import */ var core_js_modules_esnext_iterator_for_each_js__WEBPACK_IMPORTED_MODULE_27__ = __webpack_require__(/*! core-js/modules/esnext.iterator.for-each.js */ "./node_modules/core-js/modules/esnext.iterator.for-each.js");
/* harmony import */ var core_js_modules_esnext_iterator_for_each_js__WEBPACK_IMPORTED_MODULE_27___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_esnext_iterator_for_each_js__WEBPACK_IMPORTED_MODULE_27__);
/* harmony import */ var core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_28__ = __webpack_require__(/*! core-js/modules/web.dom-collections.for-each.js */ "./node_modules/core-js/modules/web.dom-collections.for-each.js");
/* harmony import */ var core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_28___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_28__);
/* harmony import */ var core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_29__ = __webpack_require__(/*! core-js/modules/web.dom-collections.iterator.js */ "./node_modules/core-js/modules/web.dom-collections.iterator.js");
/* harmony import */ var core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_29___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_29__);
/* harmony import */ var core_js_modules_web_timers_js__WEBPACK_IMPORTED_MODULE_30__ = __webpack_require__(/*! core-js/modules/web.timers.js */ "./node_modules/core-js/modules/web.timers.js");
/* harmony import */ var core_js_modules_web_timers_js__WEBPACK_IMPORTED_MODULE_30___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_timers_js__WEBPACK_IMPORTED_MODULE_30__);
/* harmony import */ var _hotwired_stimulus__WEBPACK_IMPORTED_MODULE_31__ = __webpack_require__(/*! @hotwired/stimulus */ "./node_modules/@hotwired/stimulus/dist/stimulus.js");
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }































function _classCallCheck(a, n) { if (!(a instanceof n)) throw new TypeError("Cannot call a class as a function"); }
function _defineProperties(e, r) { for (var t = 0; t < r.length; t++) { var o = r[t]; o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(e, _toPropertyKey(o.key), o); } }
function _createClass(e, r, t) { return r && _defineProperties(e.prototype, r), t && _defineProperties(e, t), Object.defineProperty(e, "prototype", { writable: !1 }), e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != _typeof(t) || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }
function _callSuper(t, o, e) { return o = _getPrototypeOf(o), _possibleConstructorReturn(t, _isNativeReflectConstruct() ? Reflect.construct(o, e || [], _getPrototypeOf(t).constructor) : o.apply(t, e)); }
function _possibleConstructorReturn(t, e) { if (e && ("object" == _typeof(e) || "function" == typeof e)) return e; if (void 0 !== e) throw new TypeError("Derived constructors may only return object or undefined"); return _assertThisInitialized(t); }
function _assertThisInitialized(e) { if (void 0 === e) throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); return e; }
function _isNativeReflectConstruct() { try { var t = !Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); } catch (t) {} return (_isNativeReflectConstruct = function _isNativeReflectConstruct() { return !!t; })(); }
function _getPrototypeOf(t) { return _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf.bind() : function (t) { return t.__proto__ || Object.getPrototypeOf(t); }, _getPrototypeOf(t); }
function _inherits(t, e) { if ("function" != typeof e && null !== e) throw new TypeError("Super expression must either be null or a function"); t.prototype = Object.create(e && e.prototype, { constructor: { value: t, writable: !0, configurable: !0 } }), Object.defineProperty(t, "prototype", { writable: !1 }), e && _setPrototypeOf(t, e); }
function _setPrototypeOf(t, e) { return _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function (t, e) { return t.__proto__ = e, t; }, _setPrototypeOf(t, e); }

// Markierung für unbeantwortete Pflichtfragen
var MISSING_CLASSES = ['ring-2', 'ring-red-400', 'ring-offset-2', 'p-2', '-m-2'];
var default_1 = /*#__PURE__*/function (_Controller) {
  function default_1() {
    _classCallCheck(this, default_1);
    return _callSuper(this, default_1, arguments);
  }
  _inherits(default_1, _Controller);
  return _createClass(default_1, [{
    key: "connect",
    value: function connect() {
      this.updateView();
    }
  }, {
    key: "next",
    value: function next() {
      if (!this.validateStep()) {
        return;
      }
      if (this.currentValue < this.totalValue) {
        this.currentValue++;
        this.updateView(true);
      }
    }
  }, {
    key: "prev",
    value: function prev() {
      if (this.currentValue > 1) {
        this.currentValue--;
        this.updateView(true);
      }
    }
  }, {
    key: "goTo",
    value: function goTo(event) {
      var target = event.currentTarget;
      var step = parseInt(target.dataset.step || '1', 10);
      if (step >= 1 && step <= this.totalValue) {
        this.currentValue = step;
        this.updateView(true);
      }
    }
    /**
     * Prüft, ob im aktuellen Step alle dreiwertigen Pflichtfragen beantwortet sind.
     * Reine UX-Hilfe – die eigentliche Absicherung ist der NotNull-Constraint im Form-Type.
     */
  }, {
    key: "validateStep",
    value: function validateStep() {
      var _missing$querySelecto;
      var step = this.stepTargets[this.currentValue - 1];
      if (!step) {
        return true;
      }
      var groups = Array.from(step.querySelectorAll('[data-tristate]'));
      var isAnswered = function isAnswered(group) {
        return group.querySelector('input[type="radio"]:checked') !== null;
      };
      for (var _i = 0, _groups = groups; _i < _groups.length; _i++) {
        var _group$classList;
        var group = _groups[_i];
        var answered = isAnswered(group);
        (_group$classList = group.classList)[answered ? 'remove' : 'add'].apply(_group$classList, MISSING_CLASSES);
        group.setAttribute('aria-invalid', answered ? 'false' : 'true');
      }
      var missing = groups.find(function (group) {
        return !isAnswered(group);
      });
      if (!missing) {
        this.clearErrors();
        return true;
      }
      if (this.hasErrorTarget) {
        this.errorTarget.textContent = this.incompleteMessageValue;
        this.errorTarget.classList.remove('hidden');
      }
      missing.scrollIntoView({
        block: 'center',
        behavior: 'smooth'
      });
      (_missing$querySelecto = missing.querySelector('input[type="radio"]')) === null || _missing$querySelecto === void 0 || _missing$querySelecto.focus({
        preventScroll: true
      });
      return false;
    }
  }, {
    key: "clearErrors",
    value: function clearErrors() {
      this.element.querySelectorAll('[data-tristate]').forEach(function (group) {
        var _group$classList2;
        (_group$classList2 = group.classList).remove.apply(_group$classList2, MISSING_CLASSES);
        group.removeAttribute('aria-invalid');
      });
      if (this.hasErrorTarget) {
        this.errorTarget.textContent = '';
        this.errorTarget.classList.add('hidden');
      }
    }
  }, {
    key: "updateView",
    value: function updateView() {
      var _this = this;
      var announce = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : false;
      this.clearErrors();
      // Steps ein-/ausblenden
      this.stepTargets.forEach(function (el, index) {
        el.classList.toggle('hidden', index + 1 !== _this.currentValue);
      });
      // Step-Indikatoren aktualisieren
      this.indicatorTargets.forEach(function (el, index) {
        var stepNum = index + 1;
        var circle = el.querySelector('[data-circle]');
        var label = el.querySelector('[data-label]');
        var line = el.querySelector('[data-line]');
        if (circle) {
          circle.classList.remove('bg-cyan-600', 'text-white', 'bg-green-500', 'bg-gray-200', 'text-gray-600');
          if (stepNum === _this.currentValue) {
            circle.classList.add('bg-cyan-600', 'text-white');
          } else if (stepNum < _this.currentValue) {
            circle.classList.add('bg-green-500', 'text-white');
          } else {
            circle.classList.add('bg-gray-200', 'text-gray-600');
          }
        }
        if (label) {
          label.classList.remove('text-cyan-700', 'font-semibold', 'text-green-700', 'text-gray-500');
          if (stepNum === _this.currentValue) {
            label.classList.add('text-cyan-700', 'font-semibold');
          } else if (stepNum < _this.currentValue) {
            label.classList.add('text-green-700');
          } else {
            label.classList.add('text-gray-500');
          }
        }
        if (line) {
          line.classList.remove('bg-green-500', 'bg-gray-200');
          line.classList.add(stepNum < _this.currentValue ? 'bg-green-500' : 'bg-gray-200');
        }
      });
      // Buttons
      this.prevButtonTarget.classList.toggle('hidden', this.currentValue === 1);
      this.nextButtonTarget.classList.toggle('hidden', this.currentValue === this.totalValue);
      this.submitButtonTarget.classList.toggle('hidden', this.currentValue !== this.totalValue);
      // Schrittwechsel für Screenreader ansagen (AK-24) – nicht beim ersten
      // Rendern (connect), nur wenn der Nutzer wechselt.
      if (announce) {
        this.announceStep();
      }
    }
    /**
     * Sagt den neuen Schritt samt Position ("Schritt 2 von 5: …") in einer
     * Live-Region an. Muster wie organisation_type_controller: kurz leeren,
     * damit auch ein wiederholt gewählter Schritt erneut vorgelesen wird.
     */
  }, {
    key: "announceStep",
    value: function announceStep() {
      var _indicator$querySelec,
        _indicator$querySelec2,
        _this2 = this;
      if (!this.hasAnnouncerTarget || !this.announceTemplateValue) {
        return;
      }
      var indicator = this.indicatorTargets[this.currentValue - 1];
      var title = (_indicator$querySelec = indicator === null || indicator === void 0 || (_indicator$querySelec2 = indicator.querySelector('[data-label]')) === null || _indicator$querySelec2 === void 0 || (_indicator$querySelec2 = _indicator$querySelec2.textContent) === null || _indicator$querySelec2 === void 0 ? void 0 : _indicator$querySelec2.trim()) !== null && _indicator$querySelec !== void 0 ? _indicator$querySelec : '';
      var message = this.announceTemplateValue.replace('%current%', String(this.currentValue)).replace('%total%', String(this.totalValue)).replace('%title%', title);
      this.announcerTarget.textContent = '';
      window.setTimeout(function () {
        _this2.announcerTarget.textContent = message;
      }, 50);
    }
  }]);
}(_hotwired_stimulus__WEBPACK_IMPORTED_MODULE_31__.Controller);
default_1.targets = ['step', 'indicator', 'prevButton', 'nextButton', 'submitButton', 'error', 'announcer'];
default_1.values = {
  current: {
    type: Number,
    "default": 1
  },
  total: Number,
  incompleteMessage: String,
  announceTemplate: String
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (default_1);

/***/ },

/***/ "./node_modules/@symfony/stimulus-bridge/lazy-controller-loader.js!./assets/controllers/tom_select_controller.ts"
/*!***********************************************************************************************************************!*\
  !*** ./node_modules/@symfony/stimulus-bridge/lazy-controller-loader.js!./assets/controllers/tom_select_controller.ts ***!
  \***********************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! core-js/modules/es.symbol.js */ "./node_modules/core-js/modules/es.symbol.js");
/* harmony import */ var core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! core-js/modules/es.symbol.description.js */ "./node_modules/core-js/modules/es.symbol.description.js");
/* harmony import */ var core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! core-js/modules/es.symbol.iterator.js */ "./node_modules/core-js/modules/es.symbol.iterator.js");
/* harmony import */ var core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var core_js_modules_es_symbol_to_primitive_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! core-js/modules/es.symbol.to-primitive.js */ "./node_modules/core-js/modules/es.symbol.to-primitive.js");
/* harmony import */ var core_js_modules_es_symbol_to_primitive_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_to_primitive_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var core_js_modules_es_error_cause_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! core-js/modules/es.error.cause.js */ "./node_modules/core-js/modules/es.error.cause.js");
/* harmony import */ var core_js_modules_es_error_cause_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_error_cause_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var core_js_modules_es_error_to_string_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! core-js/modules/es.error.to-string.js */ "./node_modules/core-js/modules/es.error.to-string.js");
/* harmony import */ var core_js_modules_es_error_to_string_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_error_to_string_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var core_js_modules_es_array_concat_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! core-js/modules/es.array.concat.js */ "./node_modules/core-js/modules/es.array.concat.js");
/* harmony import */ var core_js_modules_es_array_concat_js__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_concat_js__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! core-js/modules/es.array.iterator.js */ "./node_modules/core-js/modules/es.array.iterator.js");
/* harmony import */ var core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! core-js/modules/es.array.map.js */ "./node_modules/core-js/modules/es.array.map.js");
/* harmony import */ var core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var core_js_modules_es_date_to_json_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! core-js/modules/es.date.to-json.js */ "./node_modules/core-js/modules/es.date.to-json.js");
/* harmony import */ var core_js_modules_es_date_to_json_js__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_date_to_json_js__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var core_js_modules_es_date_to_primitive_js__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! core-js/modules/es.date.to-primitive.js */ "./node_modules/core-js/modules/es.date.to-primitive.js");
/* harmony import */ var core_js_modules_es_date_to_primitive_js__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_date_to_primitive_js__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var core_js_modules_es_function_bind_js__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! core-js/modules/es.function.bind.js */ "./node_modules/core-js/modules/es.function.bind.js");
/* harmony import */ var core_js_modules_es_function_bind_js__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_function_bind_js__WEBPACK_IMPORTED_MODULE_11__);
/* harmony import */ var core_js_modules_es_function_name_js__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! core-js/modules/es.function.name.js */ "./node_modules/core-js/modules/es.function.name.js");
/* harmony import */ var core_js_modules_es_function_name_js__WEBPACK_IMPORTED_MODULE_12___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_function_name_js__WEBPACK_IMPORTED_MODULE_12__);
/* harmony import */ var core_js_modules_es_json_stringify_js__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! core-js/modules/es.json.stringify.js */ "./node_modules/core-js/modules/es.json.stringify.js");
/* harmony import */ var core_js_modules_es_json_stringify_js__WEBPACK_IMPORTED_MODULE_13___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_json_stringify_js__WEBPACK_IMPORTED_MODULE_13__);
/* harmony import */ var core_js_modules_es_number_constructor_js__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! core-js/modules/es.number.constructor.js */ "./node_modules/core-js/modules/es.number.constructor.js");
/* harmony import */ var core_js_modules_es_number_constructor_js__WEBPACK_IMPORTED_MODULE_14___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_number_constructor_js__WEBPACK_IMPORTED_MODULE_14__);
/* harmony import */ var core_js_modules_es_object_create_js__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! core-js/modules/es.object.create.js */ "./node_modules/core-js/modules/es.object.create.js");
/* harmony import */ var core_js_modules_es_object_create_js__WEBPACK_IMPORTED_MODULE_15___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_create_js__WEBPACK_IMPORTED_MODULE_15__);
/* harmony import */ var core_js_modules_es_object_define_property_js__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! core-js/modules/es.object.define-property.js */ "./node_modules/core-js/modules/es.object.define-property.js");
/* harmony import */ var core_js_modules_es_object_define_property_js__WEBPACK_IMPORTED_MODULE_16___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_define_property_js__WEBPACK_IMPORTED_MODULE_16__);
/* harmony import */ var core_js_modules_es_object_get_prototype_of_js__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! core-js/modules/es.object.get-prototype-of.js */ "./node_modules/core-js/modules/es.object.get-prototype-of.js");
/* harmony import */ var core_js_modules_es_object_get_prototype_of_js__WEBPACK_IMPORTED_MODULE_17___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_get_prototype_of_js__WEBPACK_IMPORTED_MODULE_17__);
/* harmony import */ var core_js_modules_es_object_proto_js__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! core-js/modules/es.object.proto.js */ "./node_modules/core-js/modules/es.object.proto.js");
/* harmony import */ var core_js_modules_es_object_proto_js__WEBPACK_IMPORTED_MODULE_18___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_proto_js__WEBPACK_IMPORTED_MODULE_18__);
/* harmony import */ var core_js_modules_es_object_set_prototype_of_js__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__(/*! core-js/modules/es.object.set-prototype-of.js */ "./node_modules/core-js/modules/es.object.set-prototype-of.js");
/* harmony import */ var core_js_modules_es_object_set_prototype_of_js__WEBPACK_IMPORTED_MODULE_19___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_set_prototype_of_js__WEBPACK_IMPORTED_MODULE_19__);
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_20__ = __webpack_require__(/*! core-js/modules/es.object.to-string.js */ "./node_modules/core-js/modules/es.object.to-string.js");
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_20___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_20__);
/* harmony import */ var core_js_modules_es_promise_js__WEBPACK_IMPORTED_MODULE_21__ = __webpack_require__(/*! core-js/modules/es.promise.js */ "./node_modules/core-js/modules/es.promise.js");
/* harmony import */ var core_js_modules_es_promise_js__WEBPACK_IMPORTED_MODULE_21___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_promise_js__WEBPACK_IMPORTED_MODULE_21__);
/* harmony import */ var core_js_modules_es_reflect_construct_js__WEBPACK_IMPORTED_MODULE_22__ = __webpack_require__(/*! core-js/modules/es.reflect.construct.js */ "./node_modules/core-js/modules/es.reflect.construct.js");
/* harmony import */ var core_js_modules_es_reflect_construct_js__WEBPACK_IMPORTED_MODULE_22___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_reflect_construct_js__WEBPACK_IMPORTED_MODULE_22__);
/* harmony import */ var core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_23__ = __webpack_require__(/*! core-js/modules/es.string.iterator.js */ "./node_modules/core-js/modules/es.string.iterator.js");
/* harmony import */ var core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_23___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_23__);
/* harmony import */ var core_js_modules_esnext_iterator_constructor_js__WEBPACK_IMPORTED_MODULE_24__ = __webpack_require__(/*! core-js/modules/esnext.iterator.constructor.js */ "./node_modules/core-js/modules/esnext.iterator.constructor.js");
/* harmony import */ var core_js_modules_esnext_iterator_constructor_js__WEBPACK_IMPORTED_MODULE_24___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_esnext_iterator_constructor_js__WEBPACK_IMPORTED_MODULE_24__);
/* harmony import */ var core_js_modules_esnext_iterator_map_js__WEBPACK_IMPORTED_MODULE_25__ = __webpack_require__(/*! core-js/modules/esnext.iterator.map.js */ "./node_modules/core-js/modules/esnext.iterator.map.js");
/* harmony import */ var core_js_modules_esnext_iterator_map_js__WEBPACK_IMPORTED_MODULE_25___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_esnext_iterator_map_js__WEBPACK_IMPORTED_MODULE_25__);
/* harmony import */ var core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_26__ = __webpack_require__(/*! core-js/modules/web.dom-collections.iterator.js */ "./node_modules/core-js/modules/web.dom-collections.iterator.js");
/* harmony import */ var core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_26___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_26__);
/* harmony import */ var _hotwired_stimulus__WEBPACK_IMPORTED_MODULE_27__ = __webpack_require__(/*! @hotwired/stimulus */ "./node_modules/@hotwired/stimulus/dist/stimulus.js");
/* harmony import */ var tom_select__WEBPACK_IMPORTED_MODULE_28__ = __webpack_require__(/*! tom-select */ "./node_modules/tom-select/dist/esm/tom-select.complete.js");
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }



























function _classCallCheck(a, n) { if (!(a instanceof n)) throw new TypeError("Cannot call a class as a function"); }
function _defineProperties(e, r) { for (var t = 0; t < r.length; t++) { var o = r[t]; o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(e, _toPropertyKey(o.key), o); } }
function _createClass(e, r, t) { return r && _defineProperties(e.prototype, r), t && _defineProperties(e, t), Object.defineProperty(e, "prototype", { writable: !1 }), e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != _typeof(t) || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }
function _callSuper(t, o, e) { return o = _getPrototypeOf(o), _possibleConstructorReturn(t, _isNativeReflectConstruct() ? Reflect.construct(o, e || [], _getPrototypeOf(t).constructor) : o.apply(t, e)); }
function _possibleConstructorReturn(t, e) { if (e && ("object" == _typeof(e) || "function" == typeof e)) return e; if (void 0 !== e) throw new TypeError("Derived constructors may only return object or undefined"); return _assertThisInitialized(t); }
function _assertThisInitialized(e) { if (void 0 === e) throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); return e; }
function _isNativeReflectConstruct() { try { var t = !Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); } catch (t) {} return (_isNativeReflectConstruct = function _isNativeReflectConstruct() { return !!t; })(); }
function _getPrototypeOf(t) { return _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf.bind() : function (t) { return t.__proto__ || Object.getPrototypeOf(t); }, _getPrototypeOf(t); }
function _inherits(t, e) { if ("function" != typeof e && null !== e) throw new TypeError("Super expression must either be null or a function"); t.prototype = Object.create(e && e.prototype, { constructor: { value: t, writable: !0, configurable: !0 } }), Object.defineProperty(t, "prototype", { writable: !1 }), e && _setPrototypeOf(t, e); }
function _setPrototypeOf(t, e) { return _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function (t, e) { return t.__proto__ = e, t; }, _setPrototypeOf(t, e); }


var default_1 = /*#__PURE__*/function (_Controller) {
  function default_1() {
    _classCallCheck(this, default_1);
    return _callSuper(this, default_1, arguments);
  }
  _inherits(default_1, _Controller);
  return _createClass(default_1, [{
    key: "connect",
    value: function connect() {
      var _this = this;
      var selectElement = this.element;
      this.tomSelect = new tom_select__WEBPACK_IMPORTED_MODULE_28__["default"](selectElement, {
        plugins: ['remove_button'],
        valueField: 'id',
        labelField: 'name',
        searchField: ['name'],
        create: this.createUrlValue ? this.handleCreate.bind(this) : false,
        load: this.urlValue ? this.handleLoad.bind(this) : undefined,
        render: {
          option_create: function option_create(data) {
            return "<div class=\"create\">+ <strong>".concat(_this.escapeHtml(data.input), "</strong> hinzuf\xFCgen</div>");
          }
        }
      });
      // AK-41: Auswahl für Screenreader ansagen.
      // Die Vorschläge selbst trägt Tom Select bereits barrierefrei aus:
      // role="combobox", aria-expanded, aria-controls sowie aria-activedescendant/
      // aria-selected auf den Optionen im Listbox-Dropdown. Was fehlt, ist die
      // Ansage der GETROFFENEN Auswahl. Dafür wird die Chip-Leiste (.ts-control)
      // zu einer höflichen Live-Region: Ein neu hinzugefügter Küchen-Name wird
      // vorgelesen. Der Chip-Text trägt die Aussage – kein neuer Übersetzungs-
      // schlüssel nötig. Wird nach der Initialisierung gesetzt, damit die bereits
      // vorhandenen Chips beim Laden nicht vorgelesen werden.
      this.tomSelect.control.setAttribute('aria-live', 'polite');
      this.tomSelect.control.setAttribute('aria-relevant', 'additions');
    }
  }, {
    key: "disconnect",
    value: function disconnect() {
      var _this$tomSelect;
      (_this$tomSelect = this.tomSelect) === null || _this$tomSelect === void 0 || _this$tomSelect.destroy();
    }
  }, {
    key: "handleLoad",
    value: function handleLoad(query, callback) {
      var url = "".concat(this.urlValue, "?q=").concat(encodeURIComponent(query));
      fetch(url).then(function (response) {
        return response.json();
      }).then(function (data) {
        callback(data.map(function (item) {
          return {
            id: String(item.id),
            name: item.name
          };
        }));
      })["catch"](function () {
        return callback([]);
      });
    }
  }, {
    key: "handleCreate",
    value: function handleCreate(input, callback) {
      fetch(this.createUrlValue, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          name: input
        })
      }).then(function (response) {
        return response.json();
      }).then(function (data) {
        callback({
          id: String(data.id),
          name: data.name
        });
      })["catch"](function () {
        return callback();
      });
      return true;
    }
  }, {
    key: "escapeHtml",
    value: function escapeHtml(text) {
      var div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    }
  }]);
}(_hotwired_stimulus__WEBPACK_IMPORTED_MODULE_27__.Controller);
default_1.values = {
  url: String,
  createUrl: String
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (default_1);

/***/ },

/***/ "./node_modules/@symfony/stimulus-bridge/lazy-controller-loader.js!./assets/controllers/usage_event_controller.ts"
/*!************************************************************************************************************************!*\
  !*** ./node_modules/@symfony/stimulus-bridge/lazy-controller-loader.js!./assets/controllers/usage_event_controller.ts ***!
  \************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! core-js/modules/es.symbol.js */ "./node_modules/core-js/modules/es.symbol.js");
/* harmony import */ var core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! core-js/modules/es.symbol.description.js */ "./node_modules/core-js/modules/es.symbol.description.js");
/* harmony import */ var core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! core-js/modules/es.symbol.iterator.js */ "./node_modules/core-js/modules/es.symbol.iterator.js");
/* harmony import */ var core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var core_js_modules_es_symbol_to_primitive_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! core-js/modules/es.symbol.to-primitive.js */ "./node_modules/core-js/modules/es.symbol.to-primitive.js");
/* harmony import */ var core_js_modules_es_symbol_to_primitive_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_to_primitive_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var core_js_modules_es_error_cause_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! core-js/modules/es.error.cause.js */ "./node_modules/core-js/modules/es.error.cause.js");
/* harmony import */ var core_js_modules_es_error_cause_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_error_cause_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var core_js_modules_es_error_to_string_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! core-js/modules/es.error.to-string.js */ "./node_modules/core-js/modules/es.error.to-string.js");
/* harmony import */ var core_js_modules_es_error_to_string_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_error_to_string_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var core_js_modules_es_array_for_each_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! core-js/modules/es.array.for-each.js */ "./node_modules/core-js/modules/es.array.for-each.js");
/* harmony import */ var core_js_modules_es_array_for_each_js__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_for_each_js__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var core_js_modules_es_array_from_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! core-js/modules/es.array.from.js */ "./node_modules/core-js/modules/es.array.from.js");
/* harmony import */ var core_js_modules_es_array_from_js__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_from_js__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var core_js_modules_es_array_is_array_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! core-js/modules/es.array.is-array.js */ "./node_modules/core-js/modules/es.array.is-array.js");
/* harmony import */ var core_js_modules_es_array_is_array_js__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_is_array_js__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! core-js/modules/es.array.iterator.js */ "./node_modules/core-js/modules/es.array.iterator.js");
/* harmony import */ var core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var core_js_modules_es_array_join_js__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! core-js/modules/es.array.join.js */ "./node_modules/core-js/modules/es.array.join.js");
/* harmony import */ var core_js_modules_es_array_join_js__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_join_js__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var core_js_modules_es_array_push_js__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! core-js/modules/es.array.push.js */ "./node_modules/core-js/modules/es.array.push.js");
/* harmony import */ var core_js_modules_es_array_push_js__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_push_js__WEBPACK_IMPORTED_MODULE_11__);
/* harmony import */ var core_js_modules_es_array_slice_js__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! core-js/modules/es.array.slice.js */ "./node_modules/core-js/modules/es.array.slice.js");
/* harmony import */ var core_js_modules_es_array_slice_js__WEBPACK_IMPORTED_MODULE_12___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_slice_js__WEBPACK_IMPORTED_MODULE_12__);
/* harmony import */ var core_js_modules_es_date_to_primitive_js__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! core-js/modules/es.date.to-primitive.js */ "./node_modules/core-js/modules/es.date.to-primitive.js");
/* harmony import */ var core_js_modules_es_date_to_primitive_js__WEBPACK_IMPORTED_MODULE_13___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_date_to_primitive_js__WEBPACK_IMPORTED_MODULE_13__);
/* harmony import */ var core_js_modules_es_date_to_string_js__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! core-js/modules/es.date.to-string.js */ "./node_modules/core-js/modules/es.date.to-string.js");
/* harmony import */ var core_js_modules_es_date_to_string_js__WEBPACK_IMPORTED_MODULE_14___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_date_to_string_js__WEBPACK_IMPORTED_MODULE_14__);
/* harmony import */ var core_js_modules_es_function_bind_js__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! core-js/modules/es.function.bind.js */ "./node_modules/core-js/modules/es.function.bind.js");
/* harmony import */ var core_js_modules_es_function_bind_js__WEBPACK_IMPORTED_MODULE_15___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_function_bind_js__WEBPACK_IMPORTED_MODULE_15__);
/* harmony import */ var core_js_modules_es_function_name_js__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! core-js/modules/es.function.name.js */ "./node_modules/core-js/modules/es.function.name.js");
/* harmony import */ var core_js_modules_es_function_name_js__WEBPACK_IMPORTED_MODULE_16___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_function_name_js__WEBPACK_IMPORTED_MODULE_16__);
/* harmony import */ var core_js_modules_es_number_constructor_js__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! core-js/modules/es.number.constructor.js */ "./node_modules/core-js/modules/es.number.constructor.js");
/* harmony import */ var core_js_modules_es_number_constructor_js__WEBPACK_IMPORTED_MODULE_17___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_number_constructor_js__WEBPACK_IMPORTED_MODULE_17__);
/* harmony import */ var core_js_modules_es_object_create_js__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! core-js/modules/es.object.create.js */ "./node_modules/core-js/modules/es.object.create.js");
/* harmony import */ var core_js_modules_es_object_create_js__WEBPACK_IMPORTED_MODULE_18___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_create_js__WEBPACK_IMPORTED_MODULE_18__);
/* harmony import */ var core_js_modules_es_object_define_property_js__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__(/*! core-js/modules/es.object.define-property.js */ "./node_modules/core-js/modules/es.object.define-property.js");
/* harmony import */ var core_js_modules_es_object_define_property_js__WEBPACK_IMPORTED_MODULE_19___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_define_property_js__WEBPACK_IMPORTED_MODULE_19__);
/* harmony import */ var core_js_modules_es_object_get_prototype_of_js__WEBPACK_IMPORTED_MODULE_20__ = __webpack_require__(/*! core-js/modules/es.object.get-prototype-of.js */ "./node_modules/core-js/modules/es.object.get-prototype-of.js");
/* harmony import */ var core_js_modules_es_object_get_prototype_of_js__WEBPACK_IMPORTED_MODULE_20___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_get_prototype_of_js__WEBPACK_IMPORTED_MODULE_20__);
/* harmony import */ var core_js_modules_es_object_keys_js__WEBPACK_IMPORTED_MODULE_21__ = __webpack_require__(/*! core-js/modules/es.object.keys.js */ "./node_modules/core-js/modules/es.object.keys.js");
/* harmony import */ var core_js_modules_es_object_keys_js__WEBPACK_IMPORTED_MODULE_21___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_keys_js__WEBPACK_IMPORTED_MODULE_21__);
/* harmony import */ var core_js_modules_es_object_proto_js__WEBPACK_IMPORTED_MODULE_22__ = __webpack_require__(/*! core-js/modules/es.object.proto.js */ "./node_modules/core-js/modules/es.object.proto.js");
/* harmony import */ var core_js_modules_es_object_proto_js__WEBPACK_IMPORTED_MODULE_22___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_proto_js__WEBPACK_IMPORTED_MODULE_22__);
/* harmony import */ var core_js_modules_es_object_set_prototype_of_js__WEBPACK_IMPORTED_MODULE_23__ = __webpack_require__(/*! core-js/modules/es.object.set-prototype-of.js */ "./node_modules/core-js/modules/es.object.set-prototype-of.js");
/* harmony import */ var core_js_modules_es_object_set_prototype_of_js__WEBPACK_IMPORTED_MODULE_23___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_set_prototype_of_js__WEBPACK_IMPORTED_MODULE_23__);
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_24__ = __webpack_require__(/*! core-js/modules/es.object.to-string.js */ "./node_modules/core-js/modules/es.object.to-string.js");
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_24___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_24__);
/* harmony import */ var core_js_modules_es_promise_js__WEBPACK_IMPORTED_MODULE_25__ = __webpack_require__(/*! core-js/modules/es.promise.js */ "./node_modules/core-js/modules/es.promise.js");
/* harmony import */ var core_js_modules_es_promise_js__WEBPACK_IMPORTED_MODULE_25___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_promise_js__WEBPACK_IMPORTED_MODULE_25__);
/* harmony import */ var core_js_modules_es_reflect_construct_js__WEBPACK_IMPORTED_MODULE_26__ = __webpack_require__(/*! core-js/modules/es.reflect.construct.js */ "./node_modules/core-js/modules/es.reflect.construct.js");
/* harmony import */ var core_js_modules_es_reflect_construct_js__WEBPACK_IMPORTED_MODULE_26___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_reflect_construct_js__WEBPACK_IMPORTED_MODULE_26__);
/* harmony import */ var core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_27__ = __webpack_require__(/*! core-js/modules/es.regexp.exec.js */ "./node_modules/core-js/modules/es.regexp.exec.js");
/* harmony import */ var core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_27___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_27__);
/* harmony import */ var core_js_modules_es_regexp_test_js__WEBPACK_IMPORTED_MODULE_28__ = __webpack_require__(/*! core-js/modules/es.regexp.test.js */ "./node_modules/core-js/modules/es.regexp.test.js");
/* harmony import */ var core_js_modules_es_regexp_test_js__WEBPACK_IMPORTED_MODULE_28___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_regexp_test_js__WEBPACK_IMPORTED_MODULE_28__);
/* harmony import */ var core_js_modules_es_regexp_to_string_js__WEBPACK_IMPORTED_MODULE_29__ = __webpack_require__(/*! core-js/modules/es.regexp.to-string.js */ "./node_modules/core-js/modules/es.regexp.to-string.js");
/* harmony import */ var core_js_modules_es_regexp_to_string_js__WEBPACK_IMPORTED_MODULE_29___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_regexp_to_string_js__WEBPACK_IMPORTED_MODULE_29__);
/* harmony import */ var core_js_modules_es_set_js__WEBPACK_IMPORTED_MODULE_30__ = __webpack_require__(/*! core-js/modules/es.set.js */ "./node_modules/core-js/modules/es.set.js");
/* harmony import */ var core_js_modules_es_set_js__WEBPACK_IMPORTED_MODULE_30___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_set_js__WEBPACK_IMPORTED_MODULE_30__);
/* harmony import */ var core_js_modules_es_set_difference_v2_js__WEBPACK_IMPORTED_MODULE_31__ = __webpack_require__(/*! core-js/modules/es.set.difference.v2.js */ "./node_modules/core-js/modules/es.set.difference.v2.js");
/* harmony import */ var core_js_modules_es_set_difference_v2_js__WEBPACK_IMPORTED_MODULE_31___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_set_difference_v2_js__WEBPACK_IMPORTED_MODULE_31__);
/* harmony import */ var core_js_modules_es_set_intersection_v2_js__WEBPACK_IMPORTED_MODULE_32__ = __webpack_require__(/*! core-js/modules/es.set.intersection.v2.js */ "./node_modules/core-js/modules/es.set.intersection.v2.js");
/* harmony import */ var core_js_modules_es_set_intersection_v2_js__WEBPACK_IMPORTED_MODULE_32___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_set_intersection_v2_js__WEBPACK_IMPORTED_MODULE_32__);
/* harmony import */ var core_js_modules_es_set_is_disjoint_from_v2_js__WEBPACK_IMPORTED_MODULE_33__ = __webpack_require__(/*! core-js/modules/es.set.is-disjoint-from.v2.js */ "./node_modules/core-js/modules/es.set.is-disjoint-from.v2.js");
/* harmony import */ var core_js_modules_es_set_is_disjoint_from_v2_js__WEBPACK_IMPORTED_MODULE_33___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_set_is_disjoint_from_v2_js__WEBPACK_IMPORTED_MODULE_33__);
/* harmony import */ var core_js_modules_es_set_is_subset_of_v2_js__WEBPACK_IMPORTED_MODULE_34__ = __webpack_require__(/*! core-js/modules/es.set.is-subset-of.v2.js */ "./node_modules/core-js/modules/es.set.is-subset-of.v2.js");
/* harmony import */ var core_js_modules_es_set_is_subset_of_v2_js__WEBPACK_IMPORTED_MODULE_34___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_set_is_subset_of_v2_js__WEBPACK_IMPORTED_MODULE_34__);
/* harmony import */ var core_js_modules_es_set_is_superset_of_v2_js__WEBPACK_IMPORTED_MODULE_35__ = __webpack_require__(/*! core-js/modules/es.set.is-superset-of.v2.js */ "./node_modules/core-js/modules/es.set.is-superset-of.v2.js");
/* harmony import */ var core_js_modules_es_set_is_superset_of_v2_js__WEBPACK_IMPORTED_MODULE_35___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_set_is_superset_of_v2_js__WEBPACK_IMPORTED_MODULE_35__);
/* harmony import */ var core_js_modules_es_set_symmetric_difference_v2_js__WEBPACK_IMPORTED_MODULE_36__ = __webpack_require__(/*! core-js/modules/es.set.symmetric-difference.v2.js */ "./node_modules/core-js/modules/es.set.symmetric-difference.v2.js");
/* harmony import */ var core_js_modules_es_set_symmetric_difference_v2_js__WEBPACK_IMPORTED_MODULE_36___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_set_symmetric_difference_v2_js__WEBPACK_IMPORTED_MODULE_36__);
/* harmony import */ var core_js_modules_es_set_union_v2_js__WEBPACK_IMPORTED_MODULE_37__ = __webpack_require__(/*! core-js/modules/es.set.union.v2.js */ "./node_modules/core-js/modules/es.set.union.v2.js");
/* harmony import */ var core_js_modules_es_set_union_v2_js__WEBPACK_IMPORTED_MODULE_37___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_set_union_v2_js__WEBPACK_IMPORTED_MODULE_37__);
/* harmony import */ var core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_38__ = __webpack_require__(/*! core-js/modules/es.string.iterator.js */ "./node_modules/core-js/modules/es.string.iterator.js");
/* harmony import */ var core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_38___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_38__);
/* harmony import */ var core_js_modules_es_string_trim_js__WEBPACK_IMPORTED_MODULE_39__ = __webpack_require__(/*! core-js/modules/es.string.trim.js */ "./node_modules/core-js/modules/es.string.trim.js");
/* harmony import */ var core_js_modules_es_string_trim_js__WEBPACK_IMPORTED_MODULE_39___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_string_trim_js__WEBPACK_IMPORTED_MODULE_39__);
/* harmony import */ var core_js_modules_es_weak_set_js__WEBPACK_IMPORTED_MODULE_40__ = __webpack_require__(/*! core-js/modules/es.weak-set.js */ "./node_modules/core-js/modules/es.weak-set.js");
/* harmony import */ var core_js_modules_es_weak_set_js__WEBPACK_IMPORTED_MODULE_40___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_weak_set_js__WEBPACK_IMPORTED_MODULE_40__);
/* harmony import */ var core_js_modules_esnext_iterator_constructor_js__WEBPACK_IMPORTED_MODULE_41__ = __webpack_require__(/*! core-js/modules/esnext.iterator.constructor.js */ "./node_modules/core-js/modules/esnext.iterator.constructor.js");
/* harmony import */ var core_js_modules_esnext_iterator_constructor_js__WEBPACK_IMPORTED_MODULE_41___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_esnext_iterator_constructor_js__WEBPACK_IMPORTED_MODULE_41__);
/* harmony import */ var core_js_modules_esnext_iterator_for_each_js__WEBPACK_IMPORTED_MODULE_42__ = __webpack_require__(/*! core-js/modules/esnext.iterator.for-each.js */ "./node_modules/core-js/modules/esnext.iterator.for-each.js");
/* harmony import */ var core_js_modules_esnext_iterator_for_each_js__WEBPACK_IMPORTED_MODULE_42___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_esnext_iterator_for_each_js__WEBPACK_IMPORTED_MODULE_42__);
/* harmony import */ var core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_43__ = __webpack_require__(/*! core-js/modules/web.dom-collections.for-each.js */ "./node_modules/core-js/modules/web.dom-collections.for-each.js");
/* harmony import */ var core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_43___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_43__);
/* harmony import */ var core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_44__ = __webpack_require__(/*! core-js/modules/web.dom-collections.iterator.js */ "./node_modules/core-js/modules/web.dom-collections.iterator.js");
/* harmony import */ var core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_44___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_44__);
/* harmony import */ var _hotwired_stimulus__WEBPACK_IMPORTED_MODULE_45__ = __webpack_require__(/*! @hotwired/stimulus */ "./node_modules/@hotwired/stimulus/dist/stimulus.js");
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _toConsumableArray(r) { return _arrayWithoutHoles(r) || _iterableToArray(r) || _unsupportedIterableToArray(r) || _nonIterableSpread(); }
function _nonIterableSpread() { throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _unsupportedIterableToArray(r, a) { if (r) { if ("string" == typeof r) return _arrayLikeToArray(r, a); var t = {}.toString.call(r).slice(8, -1); return "Object" === t && r.constructor && (t = r.constructor.name), "Map" === t || "Set" === t ? Array.from(r) : "Arguments" === t || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(t) ? _arrayLikeToArray(r, a) : void 0; } }
function _iterableToArray(r) { if ("undefined" != typeof Symbol && null != r[Symbol.iterator] || null != r["@@iterator"]) return Array.from(r); }
function _arrayWithoutHoles(r) { if (Array.isArray(r)) return _arrayLikeToArray(r); }
function _arrayLikeToArray(r, a) { (null == a || a > r.length) && (a = r.length); for (var e = 0, n = Array(a); e < a; e++) n[e] = r[e]; return n; }
function _classCallCheck(a, n) { if (!(a instanceof n)) throw new TypeError("Cannot call a class as a function"); }
function _defineProperties(e, r) { for (var t = 0; t < r.length; t++) { var o = r[t]; o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(e, _toPropertyKey(o.key), o); } }
function _createClass(e, r, t) { return r && _defineProperties(e.prototype, r), t && _defineProperties(e, t), Object.defineProperty(e, "prototype", { writable: !1 }), e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != _typeof(t) || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }
function _callSuper(t, o, e) { return o = _getPrototypeOf(o), _possibleConstructorReturn(t, _isNativeReflectConstruct() ? Reflect.construct(o, e || [], _getPrototypeOf(t).constructor) : o.apply(t, e)); }
function _possibleConstructorReturn(t, e) { if (e && ("object" == _typeof(e) || "function" == typeof e)) return e; if (void 0 !== e) throw new TypeError("Derived constructors may only return object or undefined"); return _assertThisInitialized(t); }
function _assertThisInitialized(e) { if (void 0 === e) throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); return e; }
function _isNativeReflectConstruct() { try { var t = !Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); } catch (t) {} return (_isNativeReflectConstruct = function _isNativeReflectConstruct() { return !!t; })(); }
function _getPrototypeOf(t) { return _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf.bind() : function (t) { return t.__proto__ || Object.getPrototypeOf(t); }, _getPrototypeOf(t); }
function _inherits(t, e) { if ("function" != typeof e && null !== e) throw new TypeError("Super expression must either be null or a function"); t.prototype = Object.create(e && e.prototype, { constructor: { value: t, writable: !0, configurable: !0 } }), Object.defineProperty(t, "prototype", { writable: !1 }), e && _setPrototypeOf(t, e); }
function _setPrototypeOf(t, e) { return _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function (t, e) { return t.__proto__ = e, t; }, _setPrototypeOf(t, e); }













































var __classPrivateFieldGet = undefined && undefined.__classPrivateFieldGet || function (receiver, state, kind, f) {
  if (kind === "a" && !f) throw new TypeError("Private accessor was defined without a getter");
  if (typeof state === "function" ? receiver !== state || !f : !state.has(receiver)) throw new TypeError("Cannot read private member from an object whose class did not declare it");
  return kind === "m" ? f : kind === "a" ? f.call(receiver) : f ? f.value : state.get(receiver);
};
var _default_1_instances, _default_1_send;

/**
 * Löst ein Ereignis der Nutzungsmessung aus (Feature 11).
 *
 * Drei Arten, es zu verwenden:
 *
 * - **beim Erscheinen** — `data-usage-event-on-connect-value="true"`: sendet, sobald das Element
 *   im Dokument steht. Für die Erfolgsmeldung der Wartelisten, die nur bei Erfolg gerendert wird.
 * - **beim Klick / beim Absenden** — `data-action="click->usage-event#track"` bzw. `submit->…`.
 * - **Filterformular** — `data-action="submit->usage-event#filter"`: sammelt die gesetzten Filter.
 *
 * ⚠ **Nie warten, nie die Navigation anhalten** (Entwurf, Entscheidung 3). Umamis eigene
 * Klick-Attribute halten bei Links ohne `target="_blank"` die Navigation an, bis der Zählaufruf
 * fertig ist — bei `tel:` und `mailto:` wartete der Besucher auf die Messung. Deshalb hier ohne
 * `await`; der Tracker sendet mit `keepalive`, der Aufruf überlebt den Seitenwechsel.
 *
 * ⚠ **Ohne Zählskript tut dieser Controller nichts und wirft nichts** (EC-01, EC-06): kein Skript
 * auf der Seite, Werbeblocker, Widerspruch, offline.
 *
 * ⚠ **Nie Werte, die eine Person beschreiben.** Daten kommen ausschließlich aus
 * `data-usage-event-data-value`, das die Vorlage fest setzt — nie aus `href`, Formularfeldern mit
 * Freitext oder dem Seitentext. Die Weiterleitung weist alles andere ohnehin ab.
 */
var default_1 = /*#__PURE__*/function (_Controller) {
  function default_1() {
    var _this;
    _classCallCheck(this, default_1);
    _this = _callSuper(this, default_1, arguments);
    _default_1_instances.add(_this);
    return _this;
  }
  _inherits(default_1, _Controller);
  return _createClass(default_1, [{
    key: "connect",
    value: function connect() {
      if (this.onConnectValue) {
        __classPrivateFieldGet(this, _default_1_instances, "m", _default_1_send).call(this, this.dataValue);
      }
    }
  }, {
    key: "track",
    value: function track() {
      __classPrivateFieldGet(this, _default_1_instances, "m", _default_1_send).call(this, this.dataValue);
    }
    /**
     * Filterformular der Restaurantliste (AK-13).
     *
     * Übertragen werden nur die **Namen** angehakter Ja/Nein-Felder. Der Ort (`city`) und die Küchen
     * (`cuisine[]`) gehen nur als Merker `ort` bzw. `kueche` hinaus — nie ihr Wert.
     */
  }, {
    key: "filter",
    value: function filter() {
      var formular = this.element;
      var eintraege = [];
      formular.querySelectorAll('input[type="checkbox"][value="1"]').forEach(function (feld) {
        if (feld.checked && /^[a-z_]+$/.test(feld.name)) {
          eintraege.push(feld.name);
        }
      });
      var ort = formular.querySelector('input[name="city"]');
      if (ort && ort.value.trim() !== '') {
        eintraege.push('ort');
      }
      var kuechen = formular.querySelector('select[name="cuisine[]"]');
      if (kuechen && kuechen.selectedOptions.length > 0) {
        eintraege.push('kueche');
      }
      if (eintraege.length === 0) {
        return;
      }
      __classPrivateFieldGet(this, _default_1_instances, "m", _default_1_send).call(this, {
        filter: _toConsumableArray(new Set(eintraege)).join(',')
      });
    }
  }]);
}(_hotwired_stimulus__WEBPACK_IMPORTED_MODULE_45__.Controller);
_default_1_instances = new WeakSet(), _default_1_send = function _default_1_send(daten) {
  try {
    var umami = window.umami;
    if (!umami || typeof umami.track !== 'function' || this.nameValue === '') {
      return;
    }
    var ergebnis = umami.track(this.nameValue, Object.keys(daten).length > 0 ? daten : undefined);
    void Promise.resolve(ergebnis)["catch"](function () {
      // Die Messung darf nie einen Fehler in die Seite tragen.
    });
  } catch (_unused) {
    // Siehe oben.
  }
};
default_1.values = {
  name: String,
  data: {
    type: Object,
    "default": {}
  },
  onConnect: {
    type: Boolean,
    "default": false
  }
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (default_1);

/***/ },

/***/ "./node_modules/@symfony/stimulus-bridge/lazy-controller-loader.js!./assets/controllers/usage_opt_out_controller.ts"
/*!**************************************************************************************************************************!*\
  !*** ./node_modules/@symfony/stimulus-bridge/lazy-controller-loader.js!./assets/controllers/usage_opt_out_controller.ts ***!
  \**************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! core-js/modules/es.symbol.js */ "./node_modules/core-js/modules/es.symbol.js");
/* harmony import */ var core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! core-js/modules/es.symbol.description.js */ "./node_modules/core-js/modules/es.symbol.description.js");
/* harmony import */ var core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! core-js/modules/es.symbol.iterator.js */ "./node_modules/core-js/modules/es.symbol.iterator.js");
/* harmony import */ var core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var core_js_modules_es_symbol_to_primitive_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! core-js/modules/es.symbol.to-primitive.js */ "./node_modules/core-js/modules/es.symbol.to-primitive.js");
/* harmony import */ var core_js_modules_es_symbol_to_primitive_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_to_primitive_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var core_js_modules_es_error_cause_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! core-js/modules/es.error.cause.js */ "./node_modules/core-js/modules/es.error.cause.js");
/* harmony import */ var core_js_modules_es_error_cause_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_error_cause_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var core_js_modules_es_error_to_string_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! core-js/modules/es.error.to-string.js */ "./node_modules/core-js/modules/es.error.to-string.js");
/* harmony import */ var core_js_modules_es_error_to_string_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_error_to_string_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! core-js/modules/es.array.iterator.js */ "./node_modules/core-js/modules/es.array.iterator.js");
/* harmony import */ var core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var core_js_modules_es_date_to_primitive_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! core-js/modules/es.date.to-primitive.js */ "./node_modules/core-js/modules/es.date.to-primitive.js");
/* harmony import */ var core_js_modules_es_date_to_primitive_js__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_date_to_primitive_js__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var core_js_modules_es_function_bind_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! core-js/modules/es.function.bind.js */ "./node_modules/core-js/modules/es.function.bind.js");
/* harmony import */ var core_js_modules_es_function_bind_js__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_function_bind_js__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var core_js_modules_es_number_constructor_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! core-js/modules/es.number.constructor.js */ "./node_modules/core-js/modules/es.number.constructor.js");
/* harmony import */ var core_js_modules_es_number_constructor_js__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_number_constructor_js__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var core_js_modules_es_object_create_js__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! core-js/modules/es.object.create.js */ "./node_modules/core-js/modules/es.object.create.js");
/* harmony import */ var core_js_modules_es_object_create_js__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_create_js__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var core_js_modules_es_object_define_property_js__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! core-js/modules/es.object.define-property.js */ "./node_modules/core-js/modules/es.object.define-property.js");
/* harmony import */ var core_js_modules_es_object_define_property_js__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_define_property_js__WEBPACK_IMPORTED_MODULE_11__);
/* harmony import */ var core_js_modules_es_object_get_prototype_of_js__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! core-js/modules/es.object.get-prototype-of.js */ "./node_modules/core-js/modules/es.object.get-prototype-of.js");
/* harmony import */ var core_js_modules_es_object_get_prototype_of_js__WEBPACK_IMPORTED_MODULE_12___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_get_prototype_of_js__WEBPACK_IMPORTED_MODULE_12__);
/* harmony import */ var core_js_modules_es_object_proto_js__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! core-js/modules/es.object.proto.js */ "./node_modules/core-js/modules/es.object.proto.js");
/* harmony import */ var core_js_modules_es_object_proto_js__WEBPACK_IMPORTED_MODULE_13___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_proto_js__WEBPACK_IMPORTED_MODULE_13__);
/* harmony import */ var core_js_modules_es_object_set_prototype_of_js__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! core-js/modules/es.object.set-prototype-of.js */ "./node_modules/core-js/modules/es.object.set-prototype-of.js");
/* harmony import */ var core_js_modules_es_object_set_prototype_of_js__WEBPACK_IMPORTED_MODULE_14___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_set_prototype_of_js__WEBPACK_IMPORTED_MODULE_14__);
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! core-js/modules/es.object.to-string.js */ "./node_modules/core-js/modules/es.object.to-string.js");
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_15___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_15__);
/* harmony import */ var core_js_modules_es_reflect_construct_js__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! core-js/modules/es.reflect.construct.js */ "./node_modules/core-js/modules/es.reflect.construct.js");
/* harmony import */ var core_js_modules_es_reflect_construct_js__WEBPACK_IMPORTED_MODULE_16___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_reflect_construct_js__WEBPACK_IMPORTED_MODULE_16__);
/* harmony import */ var core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! core-js/modules/es.string.iterator.js */ "./node_modules/core-js/modules/es.string.iterator.js");
/* harmony import */ var core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_17___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_17__);
/* harmony import */ var core_js_modules_es_weak_set_js__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! core-js/modules/es.weak-set.js */ "./node_modules/core-js/modules/es.weak-set.js");
/* harmony import */ var core_js_modules_es_weak_set_js__WEBPACK_IMPORTED_MODULE_18___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_weak_set_js__WEBPACK_IMPORTED_MODULE_18__);
/* harmony import */ var core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__(/*! core-js/modules/web.dom-collections.iterator.js */ "./node_modules/core-js/modules/web.dom-collections.iterator.js");
/* harmony import */ var core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_19___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_19__);
/* harmony import */ var _hotwired_stimulus__WEBPACK_IMPORTED_MODULE_20__ = __webpack_require__(/*! @hotwired/stimulus */ "./node_modules/@hotwired/stimulus/dist/stimulus.js");
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _classCallCheck(a, n) { if (!(a instanceof n)) throw new TypeError("Cannot call a class as a function"); }
function _defineProperties(e, r) { for (var t = 0; t < r.length; t++) { var o = r[t]; o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(e, _toPropertyKey(o.key), o); } }
function _createClass(e, r, t) { return r && _defineProperties(e.prototype, r), t && _defineProperties(e, t), Object.defineProperty(e, "prototype", { writable: !1 }), e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != _typeof(t) || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }
function _callSuper(t, o, e) { return o = _getPrototypeOf(o), _possibleConstructorReturn(t, _isNativeReflectConstruct() ? Reflect.construct(o, e || [], _getPrototypeOf(t).constructor) : o.apply(t, e)); }
function _possibleConstructorReturn(t, e) { if (e && ("object" == _typeof(e) || "function" == typeof e)) return e; if (void 0 !== e) throw new TypeError("Derived constructors may only return object or undefined"); return _assertThisInitialized(t); }
function _assertThisInitialized(e) { if (void 0 === e) throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); return e; }
function _isNativeReflectConstruct() { try { var t = !Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); } catch (t) {} return (_isNativeReflectConstruct = function _isNativeReflectConstruct() { return !!t; })(); }
function _getPrototypeOf(t) { return _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf.bind() : function (t) { return t.__proto__ || Object.getPrototypeOf(t); }, _getPrototypeOf(t); }
function _inherits(t, e) { if ("function" != typeof e && null !== e) throw new TypeError("Super expression must either be null or a function"); t.prototype = Object.create(e && e.prototype, { constructor: { value: t, writable: !0, configurable: !0 } }), Object.defineProperty(t, "prototype", { writable: !1 }), e && _setPrototypeOf(t, e); }
function _setPrototypeOf(t, e) { return _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function (t, e) { return t.__proto__ = e, t; }, _setPrototypeOf(t, e); }




















var __classPrivateFieldGet = undefined && undefined.__classPrivateFieldGet || function (receiver, state, kind, f) {
  if (kind === "a" && !f) throw new TypeError("Private accessor was defined without a getter");
  if (typeof state === "function" ? receiver !== state || !f : !state.has(receiver)) throw new TypeError("Cannot read private member from an object whose class did not declare it");
  return kind === "m" ? f : kind === "a" ? f.call(receiver) : f ? f.value : state.get(receiver);
};
var _default_1_instances, _default_1_zeige, _default_1_istAus, _default_1_speicher;

/** Derselbe Schlüssel, den der Umami-Tracker vor jedem Versand liest. */
var SCHLUESSEL = 'umami.disabled';
/**
 * Widerspruchsschalter der Nutzungsmessung in /legal (Feature 11, AK-23).
 *
 * ⚠ **Kein Cookie** — der Bannertext „Wir nutzen nur technisch notwendige Cookies" muss wahr
 * bleiben (AK-20). Der Schalter schreibt `umami.disabled` in den Browserspeicher. Genau diesen
 * Schlüssel prüft der Umami-Tracker vor **jedem** Versand selbst (Entwurf, Entscheidung 10) — ein
 * eigener Merker wäre eine zweite Stelle, die auseinanderlaufen kann.
 *
 * ⚠ Der Knopf ist im Markup `hidden` und wird erst hier sichtbar: Ohne JavaScript wird ohnehin nicht
 * gemessen (EC-02), und ein Knopf, der nichts tut, wäre schlechter als keiner. Ist der
 * Browserspeicher gesperrt, erscheint stattdessen der Hinweis „nicht verfügbar".
 *
 * Geleerter Browserspeicher hebt den Widerspruch auf (EC-07) — ohne Cookie und ohne Konto kann der
 * Schalter sich nichts dauerhafter merken. `/legal` sagt das.
 */
var default_1 = /*#__PURE__*/function (_Controller) {
  function default_1() {
    var _this;
    _classCallCheck(this, default_1);
    _this = _callSuper(this, default_1, arguments);
    _default_1_instances.add(_this);
    return _this;
  }
  _inherits(default_1, _Controller);
  return _createClass(default_1, [{
    key: "connect",
    value: function connect() {
      var speicher = __classPrivateFieldGet(this, _default_1_instances, "m", _default_1_speicher).call(this);
      if (speicher === null) {
        this.unavailableTarget.hidden = false;
        return;
      }
      this.buttonTarget.hidden = false;
      __classPrivateFieldGet(this, _default_1_instances, "m", _default_1_zeige).call(this, speicher);
    }
  }, {
    key: "toggle",
    value: function toggle() {
      var speicher = __classPrivateFieldGet(this, _default_1_instances, "m", _default_1_speicher).call(this);
      if (speicher === null) {
        return;
      }
      if (__classPrivateFieldGet(this, _default_1_instances, "m", _default_1_istAus).call(this, speicher)) {
        speicher.removeItem(SCHLUESSEL);
      } else {
        speicher.setItem(SCHLUESSEL, '1');
      }
      __classPrivateFieldGet(this, _default_1_instances, "m", _default_1_zeige).call(this, speicher);
    }
  }]);
}(_hotwired_stimulus__WEBPACK_IMPORTED_MODULE_20__.Controller);
_default_1_instances = new WeakSet(), _default_1_zeige = function _default_1_zeige(speicher) {
  var aus = __classPrivateFieldGet(this, _default_1_instances, "m", _default_1_istAus).call(this, speicher);
  // aria-pressed="true" heißt: Die Messung ist AN. Der Zustand steht zusätzlich als Wort da —
  // Farbe trägt nie allein.
  this.buttonTarget.setAttribute('aria-pressed', aus ? 'false' : 'true');
  this.stateTarget.textContent = aus ? this.offTextValue : this.onTextValue;
}, _default_1_istAus = function _default_1_istAus(speicher) {
  return speicher.getItem(SCHLUESSEL) !== null;
}, _default_1_speicher = function _default_1_speicher() {
  try {
    var speicher = window.localStorage;
    var probe = 'endlech.speicherprobe';
    speicher.setItem(probe, '1');
    speicher.removeItem(probe);
    return speicher;
  } catch (_unused) {
    return null;
  }
};
default_1.targets = ['button', 'state', 'unavailable'];
default_1.values = {
  onText: String,
  offText: String
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (default_1);

/***/ },

/***/ "./vendor/symfony/ux-turbo/assets/dist/turbo_controller.js"
/*!*****************************************************************!*\
  !*** ./vendor/symfony/ux-turbo/assets/dist/turbo_controller.js ***!
  \*****************************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ turbo_controller_default)
/* harmony export */ });
/* harmony import */ var core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! core-js/modules/es.symbol.js */ "./node_modules/core-js/modules/es.symbol.js");
/* harmony import */ var core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! core-js/modules/es.symbol.description.js */ "./node_modules/core-js/modules/es.symbol.description.js");
/* harmony import */ var core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! core-js/modules/es.symbol.iterator.js */ "./node_modules/core-js/modules/es.symbol.iterator.js");
/* harmony import */ var core_js_modules_es_symbol_to_primitive_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! core-js/modules/es.symbol.to-primitive.js */ "./node_modules/core-js/modules/es.symbol.to-primitive.js");
/* harmony import */ var core_js_modules_es_error_cause_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! core-js/modules/es.error.cause.js */ "./node_modules/core-js/modules/es.error.cause.js");
/* harmony import */ var core_js_modules_es_error_to_string_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! core-js/modules/es.error.to-string.js */ "./node_modules/core-js/modules/es.error.to-string.js");
/* harmony import */ var core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! core-js/modules/es.array.iterator.js */ "./node_modules/core-js/modules/es.array.iterator.js");
/* harmony import */ var core_js_modules_es_date_to_primitive_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! core-js/modules/es.date.to-primitive.js */ "./node_modules/core-js/modules/es.date.to-primitive.js");
/* harmony import */ var core_js_modules_es_function_bind_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! core-js/modules/es.function.bind.js */ "./node_modules/core-js/modules/es.function.bind.js");
/* harmony import */ var core_js_modules_es_number_constructor_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! core-js/modules/es.number.constructor.js */ "./node_modules/core-js/modules/es.number.constructor.js");
/* harmony import */ var core_js_modules_es_object_create_js__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! core-js/modules/es.object.create.js */ "./node_modules/core-js/modules/es.object.create.js");
/* harmony import */ var core_js_modules_es_object_define_property_js__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! core-js/modules/es.object.define-property.js */ "./node_modules/core-js/modules/es.object.define-property.js");
/* harmony import */ var core_js_modules_es_object_get_prototype_of_js__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! core-js/modules/es.object.get-prototype-of.js */ "./node_modules/core-js/modules/es.object.get-prototype-of.js");
/* harmony import */ var core_js_modules_es_object_proto_js__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! core-js/modules/es.object.proto.js */ "./node_modules/core-js/modules/es.object.proto.js");
/* harmony import */ var core_js_modules_es_object_set_prototype_of_js__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! core-js/modules/es.object.set-prototype-of.js */ "./node_modules/core-js/modules/es.object.set-prototype-of.js");
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! core-js/modules/es.object.to-string.js */ "./node_modules/core-js/modules/es.object.to-string.js");
/* harmony import */ var core_js_modules_es_reflect_construct_js__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! core-js/modules/es.reflect.construct.js */ "./node_modules/core-js/modules/es.reflect.construct.js");
/* harmony import */ var core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! core-js/modules/es.string.iterator.js */ "./node_modules/core-js/modules/es.string.iterator.js");
/* harmony import */ var core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! core-js/modules/web.dom-collections.iterator.js */ "./node_modules/core-js/modules/web.dom-collections.iterator.js");
/* harmony import */ var _hotwired_stimulus__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__(/*! @hotwired/stimulus */ "./node_modules/@hotwired/stimulus/dist/stimulus.js");
/* harmony import */ var _hotwired_turbo__WEBPACK_IMPORTED_MODULE_20__ = __webpack_require__(/*! @hotwired/turbo */ "./node_modules/@hotwired/turbo/dist/turbo.es2017-esm.js");
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }



















function _defineProperties(e, r) { for (var t = 0; t < r.length; t++) { var o = r[t]; o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(e, _toPropertyKey(o.key), o); } }
function _createClass(e, r, t) { return r && _defineProperties(e.prototype, r), t && _defineProperties(e, t), Object.defineProperty(e, "prototype", { writable: !1 }), e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != _typeof(t) || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }
function _classCallCheck(a, n) { if (!(a instanceof n)) throw new TypeError("Cannot call a class as a function"); }
function _callSuper(t, o, e) { return o = _getPrototypeOf(o), _possibleConstructorReturn(t, _isNativeReflectConstruct() ? Reflect.construct(o, e || [], _getPrototypeOf(t).constructor) : o.apply(t, e)); }
function _possibleConstructorReturn(t, e) { if (e && ("object" == _typeof(e) || "function" == typeof e)) return e; if (void 0 !== e) throw new TypeError("Derived constructors may only return object or undefined"); return _assertThisInitialized(t); }
function _assertThisInitialized(e) { if (void 0 === e) throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); return e; }
function _isNativeReflectConstruct() { try { var t = !Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); } catch (t) {} return (_isNativeReflectConstruct = function _isNativeReflectConstruct() { return !!t; })(); }
function _getPrototypeOf(t) { return _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf.bind() : function (t) { return t.__proto__ || Object.getPrototypeOf(t); }, _getPrototypeOf(t); }
function _inherits(t, e) { if ("function" != typeof e && null !== e) throw new TypeError("Super expression must either be null or a function"); t.prototype = Object.create(e && e.prototype, { constructor: { value: t, writable: !0, configurable: !0 } }), Object.defineProperty(t, "prototype", { writable: !1 }), e && _setPrototypeOf(t, e); }
function _setPrototypeOf(t, e) { return _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function (t, e) { return t.__proto__ = e, t; }, _setPrototypeOf(t, e); }
// src/turbo_controller.ts


var turbo_controller_default = /*#__PURE__*/function (_Controller) {
  function turbo_controller_default() {
    _classCallCheck(this, turbo_controller_default);
    return _callSuper(this, turbo_controller_default, arguments);
  }
  _inherits(turbo_controller_default, _Controller);
  return _createClass(turbo_controller_default);
}(_hotwired_stimulus__WEBPACK_IMPORTED_MODULE_19__.Controller);


/***/ }

},
/******/ __webpack_require__ => { // webpackRuntimeModules
/******/ var __webpack_exec__ = (moduleId) => (__webpack_require__(__webpack_require__.s = moduleId))
/******/ __webpack_require__.O(0, ["vendors-node_modules_hotwired_turbo_dist_turbo_es2017-esm_js-node_modules_symfony_stimulus-br-356d2f"], () => (__webpack_exec__("./assets/app.ts")));
/******/ var __webpack_exports__ = __webpack_require__.O();
/******/ }
]);
//# sourceMappingURL=data:application/json;charset=utf-8;base64,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Q0FBQW9DLE1BQU0sR0FBRztFQUNacVMsTUFBTSxFQUFFdlQsTUFBTTtFQUNkd1QsT0FBTyxFQUFFeFQ7Q0FDWjs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7O0FDekJMO0FBQ2dEO0FBQ3ZCO0FBQ3pCLElBQUl5VCx3QkFBd0IsMEJBQUExVSxXQUFBO0VBQUEsU0FBQTBVLHlCQUFBO0lBQUF4VSxlQUFBLE9BQUF3VSx3QkFBQTtJQUFBLE9BQUF0USxVQUFBLE9BQUFzUSx3QkFBQSxFQUFBclEsU0FBQTtFQUFBO0VBQUFoRSxTQUFBLENBQUFxVSx3QkFBQSxFQUFBMVUsV0FBQTtFQUFBLE9BQUFNLFlBQUEsQ0FBQW9VLHdCQUFBO0FBQUEsRUFBaUI1VSwyREFBVSxDQUN0RCIsInNvdXJjZXMiOlsid2VicGFjazovLy8uL2Fzc2V0cy9hcHAudHMiLCJ3ZWJwYWNrOi8vLyBcXC5banRdc3giLCJ3ZWJwYWNrOi8vLy4vYXNzZXRzL3N0aW11bHVzX2Jvb3RzdHJhcC50cyIsIndlYnBhY2s6Ly8vLi9hc3NldHMvc3R5bGVzL2FwcC5jc3M/NmJlNiIsIndlYnBhY2s6Ly8vLi9hc3NldHMvdXNhZ2UvYmVmb3JlX3NlbmQudHMiLCJ3ZWJwYWNrOi8vLy4vYXNzZXRzL2NvbnRyb2xsZXJzLmpzb24iLCJ3ZWJwYWNrOi8vLy4vYXNzZXRzL2NvbnRyb2xsZXJzL2NvbGxlY3Rpb25fZm9ybV9jb250cm9sbGVyLnRzIiwid2VicGFjazovLy8uL2Fzc2V0cy9jb250cm9sbGVycy9jb29raWVfY29uc2VudF9jb250cm9sbGVyLnRzIiwid2VicGFjazovLy8uL2Fzc2V0cy9jb250cm9sbGVycy9jc3JmX3Byb3RlY3Rpb25fY29udHJvbGxlci50cz81ZjYzIiwid2VicGFjazovLy8uL2Fzc2V0cy9jb250cm9sbGVycy9oZWxsb19jb250cm9sbGVyLnRzIiwid2VicGFjazovLy8uL2Fzc2V0cy9jb250cm9sbGVycy9pbWFnZV9zb3J0X2NvbnRyb2xsZXIudHMiLCJ3ZWJwYWNrOi8vLy4vYXNzZXRzL2NvbnRyb2xsZXJzL2xhbmd1YWdlX3N3aXRjaGVyX2NvbnRyb2xsZXIudHMiLCJ3ZWJwYWNrOi8vLy4vYXNzZXRzL2NvbnRyb2xsZXJzL25hdl9kcm9wZG93bl9jb250cm9sbGVyLnRzIiwid2VicGFjazovLy8uL2Fzc2V0cy9jb250cm9sbGVycy9vcGVuaW5nX2hvdXJzX2Zvcm1fY29udHJvbGxlci50cyIsIndlYnBhY2s6Ly8vLi9hc3NldHMvY29udHJvbGxlcnMvb3JnYW5pc2F0aW9uX3R5cGVfY29udHJvbGxlci50cyIsIndlYnBhY2s6Ly8vLi9hc3NldHMvY29udHJvbGxlcnMvcGFzc2tleV91aV9jb250cm9sbGVyLnRzIiwid2VicGFjazovLy8uL2Fzc2V0cy9jb250cm9sbGVycy9zdWdnZXN0aW9uX3dpemFyZF9jb250cm9sbGVyLnRzIiwid2VicGFjazovLy8uL2Fzc2V0cy9jb250cm9sbGVycy90b21fc2VsZWN0X2NvbnRyb2xsZXIudHMiLCJ3ZWJwYWNrOi8vLy4vYXNzZXRzL2NvbnRyb2xsZXJzL3VzYWdlX2V2ZW50X2NvbnRyb2xsZXIudHMiLCJ3ZWJwYWNrOi8vLy4vYXNzZXRzL2NvbnRyb2xsZXJzL3VzYWdlX29wdF9vdXRfY29udHJvbGxlci50cyIsIndlYnBhY2s6Ly8vLi92ZW5kb3Ivc3ltZm9ueS91eC10dXJiby9hc3NldHMvZGlzdC90dXJib19jb250cm9sbGVyLmpzIl0sInNvdXJjZXNDb250ZW50IjpbImltcG9ydCAnLi9zdGltdWx1c19ib290c3RyYXAnO1xuLy8gTnV0enVuZ3NtZXNzdW5nIChGZWF0dXJlIDExKTogVm9yLVZlcnNhbmQtUHLDvGZ1bmcsIGRpZSBkYXMgWsOkaGxza3JpcHQgw7xiZXIgZGF0YS1iZWZvcmUtc2VuZCBydWZ0LlxuaW1wb3J0ICcuL3VzYWdlL2JlZm9yZV9zZW5kJztcbi8qXG4gKiBXZWxjb21lIHRvIHlvdXIgYXBwJ3MgbWFpbiBKYXZhU2NyaXB0IGZpbGUhXG4gKlxuICogV2UgcmVjb21tZW5kIGluY2x1ZGluZyB0aGUgYnVpbHQgdmVyc2lvbiBvZiB0aGlzIEphdmFTY3JpcHQgZmlsZVxuICogKGFuZCBpdHMgQ1NTIGZpbGUpIGluIHlvdXIgYmFzZSBsYXlvdXQgKGJhc2UuaHRtbC50d2lnKS5cbiAqL1xuXG4vLyBhbnkgQ1NTIHlvdSBpbXBvcnQgd2lsbCBvdXRwdXQgaW50byBhIHNpbmdsZSBjc3MgZmlsZSAoYXBwLmNzcyBpbiB0aGlzIGNhc2UpXG5pbXBvcnQgJy4vc3R5bGVzL2FwcC5jc3MnO1xuXG4vLyBUb20gU2VsZWN0IENTUyBmw7xyIEF1dG9jb21wbGV0ZS1TZWxlY3RzXG5pbXBvcnQgJ3RvbS1zZWxlY3QvZGlzdC9jc3MvdG9tLXNlbGVjdC5jc3MnO1xuXG4vLyBHTGlnaHRib3gg4oCTIExpZ2h0Ym94IGbDvHIgUmVzdGF1cmFudC1Gb3Rvc1xuaW1wb3J0IEdMaWdodGJveCBmcm9tICdnbGlnaHRib3gnO1xuaW1wb3J0ICdnbGlnaHRib3gvZGlzdC9jc3MvZ2xpZ2h0Ym94LmNzcyc7XG5cbmRvY3VtZW50LmFkZEV2ZW50TGlzdGVuZXIoJ0RPTUNvbnRlbnRMb2FkZWQnLCAoKSA9PiB7XG4gICAgR0xpZ2h0Ym94KHsgc2VsZWN0b3I6ICcuZ2xpZ2h0Ym94JyB9KTtcbn0pO1xuXG4vLyBQV0E6IFNlcnZpY2UgV29ya2VyIHJlZ2lzdHJpZXJlbiAoT2ZmbGluZS1TdXBwb3J0LCBpbnN0YWxsaWVyYmFyIOKAkyBJc3N1ZSAjODMpXG5pZiAoJ3NlcnZpY2VXb3JrZXInIGluIG5hdmlnYXRvcikge1xuICAgIHdpbmRvdy5hZGRFdmVudExpc3RlbmVyKCdsb2FkJywgKCkgPT4ge1xuICAgICAgICBuYXZpZ2F0b3Iuc2VydmljZVdvcmtlci5yZWdpc3RlcignL3N3LmpzJywgeyBzY29wZTogJy8nIH0pLmNhdGNoKCgpID0+IHtcbiAgICAgICAgICAgIC8vIFJlZ2lzdHJpZXJ1bmcgZmVobGdlc2NobGFnZW4g4oCTIEFwcCBmdW5rdGlvbmllcnQgb2huZSBTVyB3ZWl0ZXIuXG4gICAgICAgIH0pO1xuICAgIH0pO1xufVxuIiwidmFyIG1hcCA9IHtcblx0XCIuL2NvbGxlY3Rpb25fZm9ybV9jb250cm9sbGVyLnRzXCI6IFwiLi9ub2RlX21vZHVsZXMvQHN5bWZvbnkvc3RpbXVsdXMtYnJpZGdlL2xhenktY29udHJvbGxlci1sb2FkZXIuanMhLi9hc3NldHMvY29udHJvbGxlcnMvY29sbGVjdGlvbl9mb3JtX2NvbnRyb2xsZXIudHNcIixcblx0XCIuL2Nvb2tpZV9jb25zZW50X2NvbnRyb2xsZXIudHNcIjogXCIuL25vZGVfbW9kdWxlcy9Ac3ltZm9ueS9zdGltdWx1cy1icmlkZ2UvbGF6eS1jb250cm9sbGVyLWxvYWRlci5qcyEuL2Fzc2V0cy9jb250cm9sbGVycy9jb29raWVfY29uc2VudF9jb250cm9sbGVyLnRzXCIsXG5cdFwiLi9jc3JmX3Byb3RlY3Rpb25fY29udHJvbGxlci50c1wiOiBcIi4vbm9kZV9tb2R1bGVzL0BzeW1mb255L3N0aW11bHVzLWJyaWRnZS9sYXp5LWNvbnRyb2xsZXItbG9hZGVyLmpzIS4vYXNzZXRzL2NvbnRyb2xsZXJzL2NzcmZfcHJvdGVjdGlvbl9jb250cm9sbGVyLnRzXCIsXG5cdFwiLi9oZWxsb19jb250cm9sbGVyLnRzXCI6IFwiLi9ub2RlX21vZHVsZXMvQHN5bWZvbnkvc3RpbXVsdXMtYnJpZGdlL2xhenktY29udHJvbGxlci1sb2FkZXIuanMhLi9hc3NldHMvY29udHJvbGxlcnMvaGVsbG9fY29udHJvbGxlci50c1wiLFxuXHRcIi4vaW1hZ2Vfc29ydF9jb250cm9sbGVyLnRzXCI6IFwiLi9ub2RlX21vZHVsZXMvQHN5bWZvbnkvc3RpbXVsdXMtYnJpZGdlL2xhenktY29udHJvbGxlci1sb2FkZXIuanMhLi9hc3NldHMvY29udHJvbGxlcnMvaW1hZ2Vfc29ydF9jb250cm9sbGVyLnRzXCIsXG5cdFwiLi9sYW5ndWFnZV9zd2l0Y2hlcl9jb250cm9sbGVyLnRzXCI6IFwiLi9ub2RlX21vZHVsZXMvQHN5bWZvbnkvc3RpbXVsdXMtYnJpZGdlL2xhenktY29udHJvbGxlci1sb2FkZXIuanMhLi9hc3NldHMvY29udHJvbGxlcnMvbGFuZ3VhZ2Vfc3dpdGNoZXJfY29udHJvbGxlci50c1wiLFxuXHRcIi4vbmF2X2Ryb3Bkb3duX2NvbnRyb2xsZXIudHNcIjogXCIuL25vZGVfbW9kdWxlcy9Ac3ltZm9ueS9zdGltdWx1cy1icmlkZ2UvbGF6eS1jb250cm9sbGVyLWxvYWRlci5qcyEuL2Fzc2V0cy9jb250cm9sbGVycy9uYXZfZHJvcGRvd25fY29udHJvbGxlci50c1wiLFxuXHRcIi4vb3BlbmluZ19ob3Vyc19mb3JtX2NvbnRyb2xsZXIudHNcIjogXCIuL25vZGVfbW9kdWxlcy9Ac3ltZm9ueS9zdGltdWx1cy1icmlkZ2UvbGF6eS1jb250cm9sbGVyLWxvYWRlci5qcyEuL2Fzc2V0cy9jb250cm9sbGVycy9vcGVuaW5nX2hvdXJzX2Zvcm1fY29udHJvbGxlci50c1wiLFxuXHRcIi4vb3JnYW5pc2F0aW9uX3R5cGVfY29udHJvbGxlci50c1wiOiBcIi4vbm9kZV9tb2R1bGVzL0BzeW1mb255L3N0aW11bHVzLWJyaWRnZS9sYXp5LWNvbnRyb2xsZXItbG9hZGVyLmpzIS4vYXNzZXRzL2NvbnRyb2xsZXJzL29yZ2FuaXNhdGlvbl90eXBlX2NvbnRyb2xsZXIudHNcIixcblx0XCIuL3Bhc3NrZXlfdWlfY29udHJvbGxlci50c1wiOiBcIi4vbm9kZV9tb2R1bGVzL0BzeW1mb255L3N0aW11bHVzLWJyaWRnZS9sYXp5LWNvbnRyb2xsZXItbG9hZGVyLmpzIS4vYXNzZXRzL2NvbnRyb2xsZXJzL3Bhc3NrZXlfdWlfY29udHJvbGxlci50c1wiLFxuXHRcIi4vc3VnZ2VzdGlvbl93aXphcmRfY29udHJvbGxlci50c1wiOiBcIi4vbm9kZV9tb2R1bGVzL0BzeW1mb255L3N0aW11bHVzLWJyaWRnZS9sYXp5LWNvbnRyb2xsZXItbG9hZGVyLmpzIS4vYXNzZXRzL2NvbnRyb2xsZXJzL3N1Z2dlc3Rpb25fd2l6YXJkX2NvbnRyb2xsZXIudHNcIixcblx0XCIuL3RvbV9zZWxlY3RfY29udHJvbGxlci50c1wiOiBcIi4vbm9kZV9tb2R1bGVzL0BzeW1mb255L3N0aW11bHVzLWJyaWRnZS9sYXp5LWNvbnRyb2xsZXItbG9hZGVyLmpzIS4vYXNzZXRzL2NvbnRyb2xsZXJzL3RvbV9zZWxlY3RfY29udHJvbGxlci50c1wiLFxuXHRcIi4vdXNhZ2VfZXZlbnRfY29udHJvbGxlci50c1wiOiBcIi4vbm9kZV9tb2R1bGVzL0BzeW1mb255L3N0aW11bHVzLWJyaWRnZS9sYXp5LWNvbnRyb2xsZXItbG9hZGVyLmpzIS4vYXNzZXRzL2NvbnRyb2xsZXJzL3VzYWdlX2V2ZW50X2NvbnRyb2xsZXIudHNcIixcblx0XCIuL3VzYWdlX29wdF9vdXRfY29udHJvbGxlci50c1wiOiBcIi4vbm9kZV9tb2R1bGVzL0BzeW1mb255L3N0aW11bHVzLWJyaWRnZS9sYXp5LWNvbnRyb2xsZXItbG9hZGVyLmpzIS4vYXNzZXRzL2NvbnRyb2xsZXJzL3VzYWdlX29wdF9vdXRfY29udHJvbGxlci50c1wiXG59O1xuXG5cbmZ1bmN0aW9uIHdlYnBhY2tDb250ZXh0KHJlcSkge1xuXHR2YXIgaWQgPSB3ZWJwYWNrQ29udGV4dFJlc29sdmUocmVxKTtcblx0cmV0dXJuIF9fd2VicGFja19yZXF1aXJlX18oaWQpO1xufVxuZnVuY3Rpb24gd2VicGFja0NvbnRleHRSZXNvbHZlKHJlcSkge1xuXHRpZighX193ZWJwYWNrX3JlcXVpcmVfXy5vKG1hcCwgcmVxKSkge1xuXHRcdHZhciBlID0gbmV3IEVycm9yKFwiQ2Fubm90IGZpbmQgbW9kdWxlICdcIiArIHJlcSArIFwiJ1wiKTtcblx0XHRlLmNvZGUgPSAnTU9EVUxFX05PVF9GT1VORCc7XG5cdFx0dGhyb3cgZTtcblx0fVxuXHRyZXR1cm4gbWFwW3JlcV07XG59XG53ZWJwYWNrQ29udGV4dC5rZXlzID0gZnVuY3Rpb24gd2VicGFja0NvbnRleHRLZXlzKCkge1xuXHRyZXR1cm4gT2JqZWN0LmtleXMobWFwKTtcbn07XG53ZWJwYWNrQ29udGV4dC5yZXNvbHZlID0gd2VicGFja0NvbnRleHRSZXNvbHZlO1xubW9kdWxlLmV4cG9ydHMgPSB3ZWJwYWNrQ29udGV4dDtcbndlYnBhY2tDb250ZXh0LmlkID0gXCIuL2Fzc2V0cy9jb250cm9sbGVycyBzeW5jIHJlY3Vyc2l2ZSAuL25vZGVfbW9kdWxlcy9Ac3ltZm9ueS9zdGltdWx1cy1icmlkZ2UvbGF6eS1jb250cm9sbGVyLWxvYWRlci5qcyEgXFxcXC5banRdc3g/JFwiOyIsImltcG9ydCB7IHN0YXJ0U3RpbXVsdXNBcHAgfSBmcm9tICdAc3ltZm9ueS9zdGltdWx1cy1icmlkZ2UnO1xuaW1wb3J0IHsgQXV0aGVudGljYXRpb25Db250cm9sbGVyLCBSZWdpc3RyYXRpb25Db250cm9sbGVyIH0gZnJvbSAnQHdlYi1hdXRoL3dlYmF1dGhuLXN0aW11bHVzJztcblxuLy8gUmVnaXN0ZXJzIFN0aW11bHVzIGNvbnRyb2xsZXJzIGZyb20gY29udHJvbGxlcnMuanNvbiBhbmQgaW4gdGhlIGNvbnRyb2xsZXJzLyBkaXJlY3RvcnlcbmV4cG9ydCBjb25zdCBhcHAgPSBzdGFydFN0aW11bHVzQXBwKHJlcXVpcmUuY29udGV4dChcbiAgICAnQHN5bWZvbnkvc3RpbXVsdXMtYnJpZGdlL2xhenktY29udHJvbGxlci1sb2FkZXIhLi9jb250cm9sbGVycycsXG4gICAgdHJ1ZSxcbiAgICAvXFwuW2p0XXN4PyQvXG4pKTtcbi8vIHJlZ2lzdGVyIGFueSBjdXN0b20sIDNyZCBwYXJ0eSBjb250cm9sbGVycyBoZXJlXG5cbi8vIFBhc3NrZXlzOiBEaWUgYmVpZGVuIENvbnRyb2xsZXIgZGVzIFdlYkF1dGhuLUJ1bmRsZXMgYnJpbmdlbiBkZW5cbi8vIFdlYkF1dGhuLUFibGF1ZiBzYW10IGJhc2U2NHVybC1Lb2RpZXJ1bmcgdW5kIEZlaGxlcmtsYXNzZW4gbWl0LlxuLy9cbi8vIEJld3Vzc3QgaGllciB1bmQgTklDSFQgaW4gY29udHJvbGxlcnMuanNvbjogRGFzIFN0aW11bHVzQnVuZGxlIGzDtnN0IGplZGVuXG4vLyBFaW50cmFnIGRvcnQgZ2VnZW4gZWluIGdsZWljaG5hbWlnZXMgQ29tcG9zZXItUGFrZXQgYXVmIOKAkyBkYXMgUGFrZXQgbGVidCBhYmVyXG4vLyBudXIgYXVmIG5wbSwgZGVyIENvbnRhaW5lci1CdWlsZCBicsOkY2hlIG1pdCBcIkNvdWxkIG5vdCBmaW5kIHBhY2thZ2VcIi5cbi8vXG4vLyBFaWdlbmUsIGt1cnplIEJlemVpY2huZXIgc3RhdHQgZGVyIGxhbmdlbiBWb3JnYWJlIGF1cyBkZXIgQnVuZGxlLURva3U6IERpZVxuLy8gVGVtcGxhdGVzIHNjaHJlaWJlbiBkaWUgZGF0YS1BdHRyaWJ1dGUgb2huZWhpbiB2b24gSGFuZCwgdW5kXG4vLyBgZGF0YS1wYXNza2V5LWF1dGgt4oCmYCBsaWVzdCBzaWNoIGJlc3NlciBhbHNcbi8vIGBkYXRhLXdlYi1hdXRoLS13ZWJhdXRobi1zdGltdWx1cy0tYXV0aGVudGljYXRpb24t4oCmYC5cbmFwcC5yZWdpc3RlcigncGFzc2tleS1hdXRoJywgQXV0aGVudGljYXRpb25Db250cm9sbGVyKTtcbmFwcC5yZWdpc3RlcigncGFzc2tleS1yZWdpc3RlcicsIFJlZ2lzdHJhdGlvbkNvbnRyb2xsZXIpO1xuIiwiLy8gZXh0cmFjdGVkIGJ5IG1pbmktY3NzLWV4dHJhY3QtcGx1Z2luXG5leHBvcnQge307IiwiLyoqXG4gKiBWb3ItVmVyc2FuZC1QcsO8ZnVuZyBkZXMgWsOkaGxza3JpcHRzIChGZWF0dXJlIDExKS5cbiAqXG4gKiBVbWFtaSBydWZ0IGRpZXNlIEZ1bmt0aW9uIHZvciAqKmplZGVtKiogWsOkaGxhdWZydWYgYXVmIChgZGF0YS1iZWZvcmUtc2VuZGAgYW0gU2tyaXB0IGluXG4gKiBiYXNlLmh0bWwudHdpZykuIEdpYnQgc2llIGBudWxsYCB6dXLDvGNrLCB2ZXJsw6Rzc3QgbmljaHRzIGRlbiBCcm93c2VyLlxuICpcbiAqIOKaoCAqKkdsb2JhbCBQcml2YWN5IENvbnRyb2wqKiBrZW5udCBkZXIgVHJhY2tlciBuaWNodCwgbnVyIOKAnkRvIE5vdCBUcmFja1wiIOKAlCBkZXNoYWxiIGhpZXIgKEFLLTIyKS5cbiAqXG4gKiDimqAgKipEaWUgUGZhZHJlZ2VsbiBzdGVoZW4gaGllciBlaW4gendlaXRlcyBNYWwsIHVuZCBkYXMgaXN0IEFic2ljaHQuKiogRGVyIFNlaXRlbmtvcGYgbMOkc3N0IGRhc1xuICogU2tyaXB0IGF1ZiBWZXJ3YWx0dW5nLCBQcm9maWwgdW5kIFRva2VuLVNlaXRlbiB3ZWcuIFR1cmJvIERyaXZlIHRhdXNjaHQgYmVpbSBOYXZpZ2llcmVuIGFiZXIgbnVyXG4gKiBkZW4gU2VpdGVuaW5oYWx0OiBFaW4gZWlubWFsIGdlbGFkZW5lciBUcmFja2VyIGJsZWlidCBha3RpdiB1bmQgesOkaGx0IDMwMCBtcyBuYWNoIGplZGVtXG4gKiBgcHVzaFN0YXRlYCDigJQgYXVjaCBhdWYgZGVtIFdlZyBuYWNoIGAvZGUvYWRtaW5gLiBUdXJibyBydWZ0IGBwdXNoU3RhdGVgIHNjaG9uIHp1IEJlZ2lubiBlaW5lc1xuICogU2VpdGVud2VjaHNlbHMsIGVpbmUgTWFya2llcnVuZyBpbSBuZXVlbiBTZWl0ZW5pbmhhbHQga8OkbWUgYWxzbyB3b23DtmdsaWNoIHp1IHNww6R0LiBEaWUgQWRyZXNzZVxuICogc3RlaHQgZGFnZWdlbiBpbSBaw6RobGF1ZnJ1ZiBzZWxic3QuIERpZXNlbGJlbiBSZWdlbG4gcHLDvGZ0IGRpZSBXZWl0ZXJsZWl0dW5nIGF1ZiBkZW0gU2VydmVyXG4gKiAoYENvbGxlY3RQYXlsb2FkTm9ybWFsaXplcmApIOKAlCBkb3J0IGFscyBHcmVuemUsIGhpZXIsIGRhbWl0IGdhciBuaWNodHMgZXJzdCBhYmdlaHQgKEFLLTA2LCBBSy0wNykuXG4gKi9cblxudHlwZSBOdXR6bGFzdCA9IHsgdXJsPzogc3RyaW5nIH0gJiBSZWNvcmQ8c3RyaW5nLCB1bmtub3duPjtcblxuY29uc3QgQVVTR0VOT01NRU5FX1BGQURFID0gL15cXC9bYS16XXsyfVxcLyhhZG1pbnxwcm9maWxlKShcXC98JCkvO1xuY29uc3QgVE9LRU4gPSAvW2EtZjAtOV17NjR9L2k7XG5cbmV4cG9ydCBmdW5jdGlvbiB2b3JWZXJzYW5kKF90eXA6IHN0cmluZywgbnV0emxhc3Q6IE51dHpsYXN0IHwgbnVsbCB8IHVuZGVmaW5lZCk6IE51dHpsYXN0IHwgbnVsbCB7XG4gICAgaWYgKCFudXR6bGFzdCkge1xuICAgICAgICByZXR1cm4gbnVsbDtcbiAgICB9XG5cbiAgICBpZiAoKG5hdmlnYXRvciBhcyBOYXZpZ2F0b3IgJiB7IGdsb2JhbFByaXZhY3lDb250cm9sPzogYm9vbGVhbiB9KS5nbG9iYWxQcml2YWN5Q29udHJvbCA9PT0gdHJ1ZSkge1xuICAgICAgICByZXR1cm4gbnVsbDtcbiAgICB9XG5cbiAgICBjb25zdCBwZmFkID0gcGZhZEF1cyhudXR6bGFzdC51cmwpO1xuICAgIGlmIChudWxsID09PSBwZmFkIHx8IEFVU0dFTk9NTUVORV9QRkFERS50ZXN0KHBmYWQpIHx8IFRPS0VOLnRlc3QocGZhZCkpIHtcbiAgICAgICAgcmV0dXJuIG51bGw7XG4gICAgfVxuXG4gICAgcmV0dXJuIG51dHpsYXN0O1xufVxuXG5mdW5jdGlvbiBwZmFkQXVzKGFkcmVzc2U6IHN0cmluZyB8IHVuZGVmaW5lZCk6IHN0cmluZyB8IG51bGwge1xuICAgIHRyeSB7XG4gICAgICAgIHJldHVybiBuZXcgVVJMKGFkcmVzc2UgPz8gJycsIHdpbmRvdy5sb2NhdGlvbi5ocmVmKS5wYXRobmFtZTtcbiAgICB9IGNhdGNoIHtcbiAgICAgICAgcmV0dXJuIG51bGw7XG4gICAgfVxufVxuXG5kZWNsYXJlIGdsb2JhbCB7XG4gICAgaW50ZXJmYWNlIFdpbmRvdyB7XG4gICAgICAgIGVuZGxlY2hOdXR6dW5nVm9yVmVyc2FuZD86IHR5cGVvZiB2b3JWZXJzYW5kO1xuICAgICAgICB1bWFtaT86IHsgdHJhY2s6IChuYW1lOiBzdHJpbmcsIGRhdGE/OiBSZWNvcmQ8c3RyaW5nLCBzdHJpbmc+KSA9PiB1bmtub3duIH07XG4gICAgfVxufVxuXG53aW5kb3cuZW5kbGVjaE51dHp1bmdWb3JWZXJzYW5kID0gdm9yVmVyc2FuZDtcbiIsImltcG9ydCBjb250cm9sbGVyXzAgZnJvbSAnQHN5bWZvbnkvdXgtdHVyYm8vZGlzdC90dXJib19jb250cm9sbGVyLmpzJztcbmV4cG9ydCBkZWZhdWx0IHtcbiAgJ3N5bWZvbnktLXV4LXR1cmJvLS10dXJiby1jb3JlJzogY29udHJvbGxlcl8wLFxufTsiLCJpbXBvcnQgeyBDb250cm9sbGVyIH0gZnJvbSAnQGhvdHdpcmVkL3N0aW11bHVzJztcblxuLypcbiAqIFN0aW11bHVzLUNvbnRyb2xsZXIgZsO8ciBkeW5hbWlzY2hlIFN5bWZvbnkgQ29sbGVjdGlvblR5cGUtRmVsZGVyLlxuICogRXJtw7ZnbGljaHQgZGFzIEhpbnp1ZsO8Z2VuIHVuZCBFbnRmZXJuZW4gdm9uIEVpbnRyw6RnZW4uXG4gKi9cbmV4cG9ydCBkZWZhdWx0IGNsYXNzIGV4dGVuZHMgQ29udHJvbGxlciB7XG4gICAgc3RhdGljIHRhcmdldHMgPSBbJ2VudHJpZXMnLCAnZW50cnknXTtcbiAgICBzdGF0aWMgdmFsdWVzID0geyBwcm90b3R5cGU6IFN0cmluZyB9O1xuXG4gICAgZGVjbGFyZSByZWFkb25seSBlbnRyaWVzVGFyZ2V0OiBIVE1MRWxlbWVudDtcbiAgICBkZWNsYXJlIHJlYWRvbmx5IGVudHJ5VGFyZ2V0czogSFRNTEVsZW1lbnRbXTtcbiAgICBkZWNsYXJlIHByb3RvdHlwZVZhbHVlOiBzdHJpbmc7XG5cbiAgICAjaW5kZXghOiBudW1iZXI7XG5cbiAgICBjb25uZWN0KCkge1xuICAgICAgICB0aGlzLiNpbmRleCA9IHRoaXMuZW50cnlUYXJnZXRzLmxlbmd0aDtcbiAgICB9XG5cbiAgICBhZGRFbnRyeSgpIHtcbiAgICAgICAgY29uc3QgaHRtbCA9IHRoaXMucHJvdG90eXBlVmFsdWUucmVwbGFjZSgvX19uYW1lX18vZywgU3RyaW5nKHRoaXMuI2luZGV4KSk7XG4gICAgICAgIHRoaXMuI2luZGV4Kys7XG5cbiAgICAgICAgY29uc3Qgd3JhcHBlciA9IGRvY3VtZW50LmNyZWF0ZUVsZW1lbnQoJ2RpdicpO1xuICAgICAgICB3cmFwcGVyLmNsYXNzTGlzdC5hZGQoJ2ZsZXgnLCAnaXRlbXMtY2VudGVyJywgJ2dhcC0yJyk7XG4gICAgICAgIHdyYXBwZXIuc2V0QXR0cmlidXRlKCdkYXRhLWNvbGxlY3Rpb24tZm9ybS10YXJnZXQnLCAnZW50cnknKTtcbiAgICAgICAgd3JhcHBlci5pbm5lckhUTUwgPSBodG1sICtcbiAgICAgICAgICAgICc8YnV0dG9uIHR5cGU9XCJidXR0b25cIiBkYXRhLWFjdGlvbj1cImNvbGxlY3Rpb24tZm9ybSNyZW1vdmVFbnRyeVwiICcgK1xuICAgICAgICAgICAgJ2NsYXNzPVwidGV4dC1yZWQtNTAwIGhvdmVyOnRleHQtcmVkLTcwMCB0ZXh0LXNtIGZvbnQtYm9sZCBweC0yIHB5LTEgc2hyaW5rLTAgdHJhbnNpdGlvblwiPicgK1xuICAgICAgICAgICAgJ1xcdTI3MTU8L2J1dHRvbj4nO1xuXG4gICAgICAgIHRoaXMuZW50cmllc1RhcmdldC5hcHBlbmRDaGlsZCh3cmFwcGVyKTtcbiAgICB9XG5cbiAgICByZW1vdmVFbnRyeShldmVudDogRXZlbnQpIHtcbiAgICAgICAgY29uc3QgdGFyZ2V0ID0gZXZlbnQudGFyZ2V0IGFzIEhUTUxFbGVtZW50O1xuICAgICAgICBjb25zdCBlbnRyeSA9IHRhcmdldC5jbG9zZXN0KCdbZGF0YS1jb2xsZWN0aW9uLWZvcm0tdGFyZ2V0PVwiZW50cnlcIl0nKTtcbiAgICAgICAgaWYgKGVudHJ5KSB7XG4gICAgICAgICAgICBlbnRyeS5yZW1vdmUoKTtcbiAgICAgICAgfVxuICAgIH1cbn1cbiIsImltcG9ydCB7IENvbnRyb2xsZXIgfSBmcm9tICdAaG90d2lyZWQvc3RpbXVsdXMnO1xuXG4vKipcbiAqIENvb2tpZS1Db25zZW50LUJhbm5lciAoSXNzdWUgIzgyKS5cbiAqXG4gKiBaZWlndCBkYXMgQmFubmVyLCB3ZW5uIG5vY2gga2VpbmUgV2FobCBnZXRyb2ZmZW4gd3VyZGUsIHNwZWljaGVydCBkaWVcbiAqIEVudHNjaGVpZHVuZyAoYWt6ZXB0aWVydC9hYmdlbGVobnQpIGluIGVpbmVtIGxhbmdsZWJpZ2VuIENvb2tpZSB1bmQgbMOkc3N0IHNpY2hcbiAqIMO8YmVyIGRlbiBGb290ZXItTGluayBcIkNvb2tpZS1FaW5zdGVsbHVuZ2VuXCIgZXJuZXV0IMO2ZmZuZW4uXG4gKlxuICogRGVyIEZvb3Rlci1MaW5rIGxpZWd0IGF1w59lcmhhbGIgZGVzIEJhbm5lci1FbGVtZW50cyB1bmQgaXN0IGRhaGVyIGVpbmUgZWlnZW5lXG4gKiBDb250cm9sbGVyLUluc3Rhbno6IHNlaW4gS2xpY2sgcnVmdCBgb3BlblNldHRpbmdzKClgIGF1ZiwgZGFzIGVpbiBGZW5zdGVyLUV2ZW50XG4gKiAoYGNvb2tpZS1jb25zZW50Om9wZW5gKSBhbnN0w7bDn3QuIERpZSBCYW5uZXItSW5zdGFueiBmw6RuZ3QgZXMgw7xiZXIgZGVuXG4gKiBgQHdpbmRvd2AtQWN0aW9uLURlc2NyaXB0b3IgYWIgKGByZW9wZW5gKS4gU28gYmxlaWJ0IGRpZSBTdGltdWx1cy1FdmVudC1EZWxlZ2F0aW9uXG4gKiBpbnRha3Qg4oCTIGF1Y2ggd2VubiBGb290ZXIgb2RlciBCYW5uZXIgZWluemVsbiAoei4gQi4gcGVyIFR1cmJvLUZyYW1lKSBuZXUgZ2VsYWRlblxuICogd2VyZGVuLlxuICovXG5leHBvcnQgZGVmYXVsdCBjbGFzcyBleHRlbmRzIENvbnRyb2xsZXIge1xuICAgIHN0YXRpYyB0YXJnZXRzID0gWydiYW5uZXInXTtcbiAgICBzdGF0aWMgdmFsdWVzID0ge1xuICAgICAgICBjb29raWVOYW1lOiB7IHR5cGU6IFN0cmluZywgZGVmYXVsdDogJ2Nvb2tpZV9jb25zZW50JyB9LFxuICAgICAgICBsaWZldGltZTogeyB0eXBlOiBOdW1iZXIsIGRlZmF1bHQ6IDM2NSB9LFxuICAgIH07XG5cbiAgICBkZWNsYXJlIHJlYWRvbmx5IGJhbm5lclRhcmdldDogSFRNTEVsZW1lbnQ7XG4gICAgZGVjbGFyZSByZWFkb25seSBoYXNCYW5uZXJUYXJnZXQ6IGJvb2xlYW47XG4gICAgZGVjbGFyZSBjb29raWVOYW1lVmFsdWU6IHN0cmluZztcbiAgICBkZWNsYXJlIGxpZmV0aW1lVmFsdWU6IG51bWJlcjtcblxuICAgIGNvbm5lY3QoKTogdm9pZCB7XG4gICAgICAgIGlmICh0aGlzLmhhc0Jhbm5lclRhcmdldCAmJiAhdGhpcy4jaGFzQ29uc2VudCgpKSB7XG4gICAgICAgICAgICB0aGlzLiNzaG93KCk7XG4gICAgICAgIH1cbiAgICB9XG5cbiAgICBhY2NlcHQoKTogdm9pZCB7XG4gICAgICAgIHRoaXMuI3NldENvbnNlbnQoJ2FjY2VwdGVkJyk7XG4gICAgICAgIHRoaXMuI2hpZGUoKTtcbiAgICB9XG5cbiAgICBkZWNsaW5lKCk6IHZvaWQge1xuICAgICAgICB0aGlzLiNzZXRDb25zZW50KCdkZWNsaW5lZCcpO1xuICAgICAgICB0aGlzLiNoaWRlKCk7XG4gICAgfVxuXG4gICAgLy8gRm9vdGVyLUluc3Rhbno6IHN0w7bDn3QgZWluIEZlbnN0ZXItRXZlbnQgYW4sIGRhcyBkaWUgQmFubmVyLUluc3RhbnogYWJmw6RuZ3QuXG4gICAgb3BlblNldHRpbmdzKCk6IHZvaWQge1xuICAgICAgICB0aGlzLmRpc3BhdGNoKCdvcGVuJyk7XG4gICAgfVxuXG4gICAgLy8gQmFubmVyLUluc3Rhbno6IHJlYWdpZXJ0IGF1ZiBkYXMgRmVuc3Rlci1FdmVudCAoY29va2llLWNvbnNlbnQ6b3BlbkB3aW5kb3cpLlxuICAgIHJlb3BlbigpOiB2b2lkIHtcbiAgICAgICAgaWYgKHRoaXMuaGFzQmFubmVyVGFyZ2V0KSB7XG4gICAgICAgICAgICB0aGlzLiNzaG93KCk7XG4gICAgICAgICAgICAvLyBOdXR6ZXJnZXRyaWdnZXJ0IChLbGljayBhdWYgXCJDb29raWUtRWluc3RlbGx1bmdlblwiKTogZGVyIEZva3VzIHNvbGwgaW5cbiAgICAgICAgICAgIC8vIGRlbiBCYW5uZXIuIEJlaW0gYXV0b21hdGlzY2hlbiBFcnNjaGVpbmVuIChjb25uZWN0KSBOSUNIVCDigJMgZG9ydCB6w7ZnZVxuICAgICAgICAgICAgLy8gZGVyIEZva3VzLUZhbmcgZGVuIGVyc3RlbiBUYWIgaW4gZGVuIEJhbm5lciwgdW5kIGRlciBTa2lwLUxpbmsgd8OkcmVcbiAgICAgICAgICAgIC8vIG5pY2h0IG1laHIgZGFzIGVyc3RlIFRhYi1aaWVsIChCRi03NCwgV0NBRyAyLjQuMSkuXG4gICAgICAgICAgICB0aGlzLmJhbm5lclRhcmdldC5mb2N1cygpO1xuICAgICAgICB9XG4gICAgfVxuXG4gICAgI3Nob3coKTogdm9pZCB7XG4gICAgICAgIHRoaXMuYmFubmVyVGFyZ2V0LmNsYXNzTGlzdC5yZW1vdmUoJ2hpZGRlbicpO1xuICAgIH1cblxuICAgICNoaWRlKCk6IHZvaWQge1xuICAgICAgICB0aGlzLmJhbm5lclRhcmdldC5jbGFzc0xpc3QuYWRkKCdoaWRkZW4nKTtcbiAgICB9XG5cbiAgICAjaGFzQ29uc2VudCgpOiBib29sZWFuIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuI3JlYWRDb29raWUodGhpcy5jb29raWVOYW1lVmFsdWUpICE9PSBudWxsO1xuICAgIH1cblxuICAgICNzZXRDb25zZW50KHZhbHVlOiAnYWNjZXB0ZWQnIHwgJ2RlY2xpbmVkJyk6IHZvaWQge1xuICAgICAgICBjb25zdCBtYXhBZ2UgPSB0aGlzLmxpZmV0aW1lVmFsdWUgKiAyNCAqIDYwICogNjA7XG4gICAgICAgIGNvbnN0IGNvb2tpZSA9IGAke3RoaXMuY29va2llTmFtZVZhbHVlfT0ke3ZhbHVlfTsgcGF0aD0vOyBtYXgtYWdlPSR7bWF4QWdlfTsgc2FtZXNpdGU9bGF4YDtcbiAgICAgICAgZG9jdW1lbnQuY29va2llID0gd2luZG93LmxvY2F0aW9uLnByb3RvY29sID09PSAnaHR0cHM6JyA/IGAke2Nvb2tpZX07IHNlY3VyZWAgOiBjb29raWU7XG4gICAgfVxuXG4gICAgI3JlYWRDb29raWUobmFtZTogc3RyaW5nKTogc3RyaW5nIHwgbnVsbCB7XG4gICAgICAgIGNvbnN0IGVzY2FwZWQgPSBuYW1lLnJlcGxhY2UoL1suKis/XiR7fSgpfFtcXF1cXFxcXS9nLCAnXFxcXCQmJyk7XG4gICAgICAgIGNvbnN0IG1hdGNoID0gZG9jdW1lbnQuY29va2llLm1hdGNoKG5ldyBSZWdFeHAoJyg/Ol58OyApJyArIGVzY2FwZWQgKyAnPShbXjtdKiknKSk7XG4gICAgICAgIHJldHVybiBtYXRjaCA/IGRlY29kZVVSSUNvbXBvbmVudChtYXRjaFsxXSkgOiBudWxsO1xuICAgIH1cbn1cbiIsImltcG9ydCB7IENvbnRyb2xsZXIgfSBmcm9tICdAaG90d2lyZWQvc3RpbXVsdXMnO1xuY29uc3QgY29udHJvbGxlciA9IGNsYXNzIGV4dGVuZHMgQ29udHJvbGxlciB7XG4gICAgY29uc3RydWN0b3IoY29udGV4dCkge1xuICAgICAgICBzdXBlcihjb250ZXh0KTtcbiAgICAgICAgdGhpcy5fX3N0aW11bHVzTGF6eUNvbnRyb2xsZXIgPSB0cnVlO1xuICAgIH1cbiAgICBpbml0aWFsaXplKCkge1xuICAgICAgICBpZiAodGhpcy5hcHBsaWNhdGlvbi5jb250cm9sbGVycy5maW5kKChjb250cm9sbGVyKSA9PiB7XG4gICAgICAgICAgICByZXR1cm4gY29udHJvbGxlci5pZGVudGlmaWVyID09PSB0aGlzLmlkZW50aWZpZXIgJiYgY29udHJvbGxlci5fX3N0aW11bHVzTGF6eUNvbnRyb2xsZXI7XG4gICAgICAgIH0pKSB7XG4gICAgICAgICAgICByZXR1cm47XG4gICAgICAgIH1cbiAgICAgICAgaW1wb3J0KCcvVXNlcnMvbWljaGFlbGZlcnJlaXJhL0RFVi9TYWFTL2VuZGxlY2gvYXNzZXRzL2NvbnRyb2xsZXJzL2NzcmZfcHJvdGVjdGlvbl9jb250cm9sbGVyLnRzJykudGhlbigoY29udHJvbGxlcikgPT4ge1xuICAgICAgICAgICAgdGhpcy5hcHBsaWNhdGlvbi5yZWdpc3Rlcih0aGlzLmlkZW50aWZpZXIsIGNvbnRyb2xsZXIuZGVmYXVsdCk7XG4gICAgICAgIH0pO1xuICAgIH1cbn07XG5leHBvcnQgeyBjb250cm9sbGVyIGFzIGRlZmF1bHQgfTsiLCJpbXBvcnQgeyBDb250cm9sbGVyIH0gZnJvbSAnQGhvdHdpcmVkL3N0aW11bHVzJztcblxuLypcbiAqIFRoaXMgaXMgYW4gZXhhbXBsZSBTdGltdWx1cyBjb250cm9sbGVyIVxuICpcbiAqIEFueSBlbGVtZW50IHdpdGggYSBkYXRhLWNvbnRyb2xsZXI9XCJoZWxsb1wiIGF0dHJpYnV0ZSB3aWxsIGNhdXNlXG4gKiB0aGlzIGNvbnRyb2xsZXIgdG8gYmUgZXhlY3V0ZWQuIFRoZSBuYW1lIFwiaGVsbG9cIiBjb21lcyBmcm9tIHRoZSBmaWxlbmFtZTpcbiAqIGhlbGxvX2NvbnRyb2xsZXIudHMgLT4gXCJoZWxsb1wiXG4gKlxuICogRGVsZXRlIHRoaXMgZmlsZSBvciBhZGFwdCBpdCBmb3IgeW91ciB1c2UhXG4gKi9cbmV4cG9ydCBkZWZhdWx0IGNsYXNzIGV4dGVuZHMgQ29udHJvbGxlciB7XG4gICAgY29ubmVjdCgpIHtcbiAgICAgICAgdGhpcy5lbGVtZW50LnRleHRDb250ZW50ID0gJ0hlbGxvIFN0aW11bHVzISBFZGl0IG1lIGluIGFzc2V0cy9jb250cm9sbGVycy9oZWxsb19jb250cm9sbGVyLnRzJztcbiAgICB9XG59XG4iLCJpbXBvcnQgeyBDb250cm9sbGVyIH0gZnJvbSAnQGhvdHdpcmVkL3N0aW11bHVzJztcbmltcG9ydCBTb3J0YWJsZSBmcm9tICdzb3J0YWJsZWpzJztcblxuLypcbiAqIFN0aW11bHVzLUNvbnRyb2xsZXIgZsO8ciBkaWUgQmlsZHNvcnRpZXJ1bmcuXG4gKiBad2VpIGdsZWljaHdlcnRpZ2UgV2VnZSwgYmVpZGUgc2VuZGVuIGRpZSBuZXVlIFJlaWhlbmZvbGdlIHBlciBQT1NUIGFuXG4gKiBkZW5zZWxiZW4gRW5kcHVua3QgKGFkbWluX3Jlc3RhdXJhbnRfaW1hZ2Vfc29ydCk6XG4gKiAgIDEuIERyYWcgJiBEcm9wIChNYXVzKSB2aWEgU29ydGFibGVKUy5cbiAqICAgMi4gQXVmL0FiLUtuw7ZwZmUgamUgQmlsZCAoVGFzdGF0dXIvb2huZSBaaWVoZW4pIHZpYSBtb3ZlVXAvbW92ZURvd24uXG4gKi9cbmV4cG9ydCBkZWZhdWx0IGNsYXNzIGV4dGVuZHMgQ29udHJvbGxlciB7XG4gICAgc3RhdGljIHRhcmdldHMgPSBbJ2xpc3QnXTtcbiAgICBzdGF0aWMgdmFsdWVzID0geyB1cmw6IFN0cmluZywgdG9rZW46IFN0cmluZyB9O1xuXG4gICAgZGVjbGFyZSByZWFkb25seSBsaXN0VGFyZ2V0OiBIVE1MRWxlbWVudDtcbiAgICBkZWNsYXJlIHVybFZhbHVlOiBzdHJpbmc7XG4gICAgZGVjbGFyZSB0b2tlblZhbHVlOiBzdHJpbmc7XG5cbiAgICBjb25uZWN0KCkge1xuICAgICAgICBTb3J0YWJsZS5jcmVhdGUodGhpcy5saXN0VGFyZ2V0LCB7XG4gICAgICAgICAgICBoYW5kbGU6ICcuZHJhZy1oYW5kbGUnLFxuICAgICAgICAgICAgZ2hvc3RDbGFzczogJ29wYWNpdHktMzAnLFxuICAgICAgICAgICAgYW5pbWF0aW9uOiAxNTAsXG4gICAgICAgICAgICBvbkVuZDogKCkgPT4ge1xuICAgICAgICAgICAgICAgIHRoaXMuI3VwZGF0ZUJ1dHRvbnMoKTtcbiAgICAgICAgICAgICAgICB2b2lkIHRoaXMuI3BlcnNpc3QoKTtcbiAgICAgICAgICAgIH0sXG4gICAgICAgIH0pO1xuXG4gICAgICAgIHRoaXMuI3VwZGF0ZUJ1dHRvbnMoKTtcbiAgICB9XG5cbiAgICBtb3ZlVXAoZXZlbnQ6IEV2ZW50KSB7XG4gICAgICAgIGNvbnN0IGJ1dHRvbiA9IGV2ZW50LmN1cnJlbnRUYXJnZXQgYXMgSFRNTEJ1dHRvbkVsZW1lbnQ7XG4gICAgICAgIGNvbnN0IHJvdyA9IGJ1dHRvbi5jbG9zZXN0PEhUTUxFbGVtZW50PignW2RhdGEtaW1hZ2UtaWRdJyk7XG4gICAgICAgIGNvbnN0IHByZXZpb3VzID0gcm93Py5wcmV2aW91c0VsZW1lbnRTaWJsaW5nO1xuICAgICAgICBpZiAoIXJvdyB8fCAhcHJldmlvdXMpIHtcbiAgICAgICAgICAgIHJldHVybjtcbiAgICAgICAgfVxuICAgICAgICBwcmV2aW91cy5iZWZvcmUocm93KTtcbiAgICAgICAgdGhpcy4jYWZ0ZXJNb3ZlKGJ1dHRvbiwgcm93KTtcbiAgICB9XG5cbiAgICBtb3ZlRG93bihldmVudDogRXZlbnQpIHtcbiAgICAgICAgY29uc3QgYnV0dG9uID0gZXZlbnQuY3VycmVudFRhcmdldCBhcyBIVE1MQnV0dG9uRWxlbWVudDtcbiAgICAgICAgY29uc3Qgcm93ID0gYnV0dG9uLmNsb3Nlc3Q8SFRNTEVsZW1lbnQ+KCdbZGF0YS1pbWFnZS1pZF0nKTtcbiAgICAgICAgY29uc3QgbmV4dCA9IHJvdz8ubmV4dEVsZW1lbnRTaWJsaW5nO1xuICAgICAgICBpZiAoIXJvdyB8fCAhbmV4dCkge1xuICAgICAgICAgICAgcmV0dXJuO1xuICAgICAgICB9XG4gICAgICAgIG5leHQuYWZ0ZXIocm93KTtcbiAgICAgICAgdGhpcy4jYWZ0ZXJNb3ZlKGJ1dHRvbiwgcm93KTtcbiAgICB9XG5cbiAgICAvLyBOYWNoIGplZGVtIFRhc3RhdHVyLVZlcnNjaGllYmVuOiBLbm9wZi1adXN0w6RuZGUgYWt0dWFsaXNpZXJlbiwgRm9rdXNcbiAgICAvLyBzaW5udm9sbCBoYWx0ZW4gKHdhbmRlcnQgZGVyIGF1c2dlbMO2c3RlIEtub3BmIGFuIGRlbiBSYW5kIHVuZCB3aXJkXG4gICAgLy8gZGVha3RpdmllcnQsIHNwcmluZ3QgZGVyIEZva3VzIGF1ZiBkZW4gR2VnZW5rbm9wZikgdW5kIHNwZWljaGVybi5cbiAgICAjYWZ0ZXJNb3ZlKGJ1dHRvbjogSFRNTEJ1dHRvbkVsZW1lbnQsIHJvdzogSFRNTEVsZW1lbnQpIHtcbiAgICAgICAgdGhpcy4jdXBkYXRlQnV0dG9ucygpO1xuXG4gICAgICAgIGlmIChidXR0b24uZGlzYWJsZWQpIHtcbiAgICAgICAgICAgIGNvbnN0IGZhbGxiYWNrID0gcm93LnF1ZXJ5U2VsZWN0b3I8SFRNTEJ1dHRvbkVsZW1lbnQ+KFxuICAgICAgICAgICAgICAgICdbZGF0YS1zb3J0LWJ1dHRvbl06bm90KFtkaXNhYmxlZF0pJyxcbiAgICAgICAgICAgICk7XG4gICAgICAgICAgICBmYWxsYmFjaz8uZm9jdXMoKTtcbiAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgIGJ1dHRvbi5mb2N1cygpO1xuICAgICAgICB9XG5cbiAgICAgICAgdm9pZCB0aGlzLiNwZXJzaXN0KCk7XG4gICAgfVxuXG4gICAgLy8gRXJzdGVzIEJpbGQga2FubiBuaWNodCBuYWNoIG9iZW4sIGxldHp0ZXMgbmljaHQgbmFjaCB1bnRlbi5cbiAgICAjdXBkYXRlQnV0dG9ucygpIHtcbiAgICAgICAgY29uc3Qgcm93cyA9IEFycmF5LmZyb20odGhpcy5saXN0VGFyZ2V0LnF1ZXJ5U2VsZWN0b3JBbGw8SFRNTEVsZW1lbnQ+KCdbZGF0YS1pbWFnZS1pZF0nKSk7XG4gICAgICAgIHJvd3MuZm9yRWFjaCgocm93LCBpbmRleCkgPT4ge1xuICAgICAgICAgICAgY29uc3QgdXAgPSByb3cucXVlcnlTZWxlY3RvcjxIVE1MQnV0dG9uRWxlbWVudD4oJ1tkYXRhLXNvcnQtYnV0dG9uPVwidXBcIl0nKTtcbiAgICAgICAgICAgIGNvbnN0IGRvd24gPSByb3cucXVlcnlTZWxlY3RvcjxIVE1MQnV0dG9uRWxlbWVudD4oJ1tkYXRhLXNvcnQtYnV0dG9uPVwiZG93blwiXScpO1xuICAgICAgICAgICAgaWYgKHVwKSB7XG4gICAgICAgICAgICAgICAgdXAuZGlzYWJsZWQgPSBpbmRleCA9PT0gMDtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIGlmIChkb3duKSB7XG4gICAgICAgICAgICAgICAgZG93bi5kaXNhYmxlZCA9IGluZGV4ID09PSByb3dzLmxlbmd0aCAtIDE7XG4gICAgICAgICAgICB9XG4gICAgICAgIH0pO1xuICAgIH1cblxuICAgIGFzeW5jICNwZXJzaXN0KCkge1xuICAgICAgICBjb25zdCBpdGVtcyA9IHRoaXMubGlzdFRhcmdldC5xdWVyeVNlbGVjdG9yQWxsPEhUTUxFbGVtZW50PignW2RhdGEtaW1hZ2UtaWRdJyk7XG4gICAgICAgIGNvbnN0IGltYWdlSWRzID0gQXJyYXkuZnJvbShpdGVtcykubWFwKChlbCkgPT4gTnVtYmVyKGVsLmRhdGFzZXQuaW1hZ2VJZCkpO1xuXG4gICAgICAgIC8vIENvdmVyLUJhZGdlIGFrdHVhbGlzaWVyZW46IG51ciBiZWltIGVyc3RlbiBFbGVtZW50IGFuemVpZ2VuXG4gICAgICAgIGl0ZW1zLmZvckVhY2goKGVsLCBpbmRleCkgPT4ge1xuICAgICAgICAgICAgY29uc3QgYmFkZ2UgPSBlbC5xdWVyeVNlbGVjdG9yKCdbZGF0YS1jb3Zlci1iYWRnZV0nKTtcbiAgICAgICAgICAgIGlmIChiYWRnZSkge1xuICAgICAgICAgICAgICAgIChiYWRnZSBhcyBIVE1MRWxlbWVudCkuc3R5bGUuZGlzcGxheSA9IGluZGV4ID09PSAwID8gJycgOiAnbm9uZSc7XG4gICAgICAgICAgICB9XG4gICAgICAgIH0pO1xuXG4gICAgICAgIGF3YWl0IGZldGNoKHRoaXMudXJsVmFsdWUsIHtcbiAgICAgICAgICAgIG1ldGhvZDogJ1BPU1QnLFxuICAgICAgICAgICAgaGVhZGVyczogeyAnQ29udGVudC1UeXBlJzogJ2FwcGxpY2F0aW9uL2pzb24nIH0sXG4gICAgICAgICAgICBib2R5OiBKU09OLnN0cmluZ2lmeSh7IF90b2tlbjogdGhpcy50b2tlblZhbHVlLCBpbWFnZUlkcyB9KSxcbiAgICAgICAgfSk7XG4gICAgfVxufVxuIiwiaW1wb3J0IHsgQ29udHJvbGxlciB9IGZyb20gJ0Bob3R3aXJlZC9zdGltdWx1cyc7XG5cbmV4cG9ydCBkZWZhdWx0IGNsYXNzIGV4dGVuZHMgQ29udHJvbGxlciB7XG4gICAgc3RhdGljIHRhcmdldHMgPSBbJ21lbnUnLCAnYnV0dG9uJywgJ2Fycm93J107XG5cbiAgICBkZWNsYXJlIHJlYWRvbmx5IG1lbnVUYXJnZXQ6IEhUTUxFbGVtZW50O1xuICAgIGRlY2xhcmUgcmVhZG9ubHkgYnV0dG9uVGFyZ2V0OiBIVE1MRWxlbWVudDtcbiAgICBkZWNsYXJlIHJlYWRvbmx5IGFycm93VGFyZ2V0OiBTVkdFbGVtZW50O1xuXG4gICAgdG9nZ2xlKGV2ZW50OiBFdmVudCk6IHZvaWQge1xuICAgICAgICBldmVudC5zdG9wUHJvcGFnYXRpb24oKTtcbiAgICAgICAgY29uc3QgaXNPcGVuID0gIXRoaXMubWVudVRhcmdldC5jbGFzc0xpc3QuY29udGFpbnMoJ2hpZGRlbicpO1xuICAgICAgICBpZiAoaXNPcGVuKSB7XG4gICAgICAgICAgICB0aGlzLmNsb3NlTWVudSgpO1xuICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgdGhpcy5vcGVuTWVudSgpO1xuICAgICAgICB9XG4gICAgfVxuXG4gICAgY2xvc2UoZXZlbnQ6IEV2ZW50KTogdm9pZCB7XG4gICAgICAgIGlmICghdGhpcy5lbGVtZW50LmNvbnRhaW5zKGV2ZW50LnRhcmdldCBhcyBOb2RlKSkge1xuICAgICAgICAgICAgdGhpcy5jbG9zZU1lbnUoKTtcbiAgICAgICAgfVxuICAgIH1cblxuICAgIC8qKlxuICAgICAqIEJGLTcxOiBFc2NhcGUgc2NobGllw590IGRhcyBNZW7DvCB1bmQgZ2lidCBkZW4gRm9rdXMgenVyw7xjay5cbiAgICAgKlxuICAgICAqIGBjbG9zZWAgaMOkbmd0IGFuIGBjbGlja0B3aW5kb3dgIHVuZCBpc3QgZGFtaXQgZWluZSBNYXVzaGFuZGx1bmcuIFdlciBkYXMgTWVuw7xcbiAgICAgKiBwZXIgVGFzdGF0dXIgw7ZmZm5ldCwga29ubnRlIGVzIG9obmUgTWF1cyBuaWNodCB3aWVkZXIgc2NobGllw59lbiDigJQgYmVpIGVpbmVtXG4gICAgICogRWxlbWVudCBtaXQgYGFyaWEtaGFzcG9wdXBgIHdpZGVyc3ByaWNodCBkYXMgZGVuIEFSSUEgQXV0aG9yaW5nIFByYWN0aWNlcy5cbiAgICAgKi9cbiAgICBjbG9zZU9uRXNjYXBlKCk6IHZvaWQge1xuICAgICAgICBpZiAodGhpcy5tZW51VGFyZ2V0LmNsYXNzTGlzdC5jb250YWlucygnaGlkZGVuJykpIHtcbiAgICAgICAgICAgIHJldHVybjtcbiAgICAgICAgfVxuXG4gICAgICAgIHRoaXMuY2xvc2VNZW51KCk7XG4gICAgICAgIHRoaXMuYnV0dG9uVGFyZ2V0LmZvY3VzKCk7XG4gICAgfVxuXG4gICAgcHJpdmF0ZSBvcGVuTWVudSgpOiB2b2lkIHtcbiAgICAgICAgdGhpcy5tZW51VGFyZ2V0LmNsYXNzTGlzdC5yZW1vdmUoJ2hpZGRlbicpO1xuICAgICAgICB0aGlzLmJ1dHRvblRhcmdldC5zZXRBdHRyaWJ1dGUoJ2FyaWEtZXhwYW5kZWQnLCAndHJ1ZScpO1xuICAgICAgICB0aGlzLmFycm93VGFyZ2V0LmNsYXNzTGlzdC5hZGQoJ3JvdGF0ZS0xODAnKTtcbiAgICB9XG5cbiAgICBwcml2YXRlIGNsb3NlTWVudSgpOiB2b2lkIHtcbiAgICAgICAgdGhpcy5tZW51VGFyZ2V0LmNsYXNzTGlzdC5hZGQoJ2hpZGRlbicpO1xuICAgICAgICB0aGlzLmJ1dHRvblRhcmdldC5zZXRBdHRyaWJ1dGUoJ2FyaWEtZXhwYW5kZWQnLCAnZmFsc2UnKTtcbiAgICAgICAgdGhpcy5hcnJvd1RhcmdldC5jbGFzc0xpc3QucmVtb3ZlKCdyb3RhdGUtMTgwJyk7XG4gICAgfVxufVxuIiwiaW1wb3J0IHsgQ29udHJvbGxlciB9IGZyb20gJ0Bob3R3aXJlZC9zdGltdWx1cyc7XG5cbi8qKlxuICogU2NobGllw590IGVpbiA8ZGV0YWlscz4tRHJvcGRvd24gYmVpIEVzY2FwZSBvZGVyIEtsaWNrIGRhbmViZW4uXG4gKlxuICogUmVpbiB6dXPDpHR6bGljaDogRGFzIEF1ZmtsYXBwZW4gc2VsYnN0IGVybGVkaWd0IDxkZXRhaWxzPiBuYXRpdiDigJMgb2huZVxuICogSmF2YVNjcmlwdCBibGVpYnQgZGFzIE1lbsO8IGFsc28gdm9sbCBiZWRpZW5iYXIsIGVzIHNjaGxpZcOfdCBzaWNoIGRhbm4gbnVyXG4gKiBuaWNodCB2b24gYWxsZWluLiBEZXNoYWxiIHdpcmQgaGllciBhdWNoIGtlaW4gYXJpYS1leHBhbmRlZCBnZXBmbGVndDpcbiAqIDxkZXRhaWxzPiBtZWxkZXQgc2VpbmVuIFp1c3RhbmQgYmVyZWl0cyBzZWxic3QgYW4gU2NyZWVucmVhZGVyLlxuICpcbiAqIERpZSBIYW5kbGVyIHNpbmQgZ2VidW5kZW5lIEtsYXNzZW5mZWxkZXIgc3RhdHQgI3ByaXZhdGUtTWV0aG9kZW46IEJhYmVsIGthbm5cbiAqIHByaXZhdGUgRmVsZGVyIGluIGRlciBhbm9ueW1lbiBDb250cm9sbGVyLUtsYXNzZSBuaWNodCDDvGJlcnNldHplblxuICogKFwiQSBjbGFzcyBuYW1lIGlzIHJlcXVpcmVkXCIpLCBvYndvaGwgdHNjIHNpZSBha3plcHRpZXJ0LlxuICovXG5leHBvcnQgZGVmYXVsdCBjbGFzcyBleHRlbmRzIENvbnRyb2xsZXI8SFRNTERldGFpbHNFbGVtZW50PiB7XG4gICAgcHJpdmF0ZSByZWFkb25seSBvbk91dHNpZGVDbGljayA9IChldmVudDogTW91c2VFdmVudCk6IHZvaWQgPT4ge1xuICAgICAgICBpZiAoIXRoaXMuZWxlbWVudC5jb250YWlucyhldmVudC50YXJnZXQgYXMgTm9kZSkpIHtcbiAgICAgICAgICAgIHRoaXMuZWxlbWVudC5vcGVuID0gZmFsc2U7XG4gICAgICAgIH1cbiAgICB9O1xuXG4gICAgcHJpdmF0ZSByZWFkb25seSBvbktleWRvd24gPSAoZXZlbnQ6IEtleWJvYXJkRXZlbnQpOiB2b2lkID0+IHtcbiAgICAgICAgaWYgKGV2ZW50LmtleSAhPT0gJ0VzY2FwZScgfHwgIXRoaXMuZWxlbWVudC5vcGVuKSB7XG4gICAgICAgICAgICByZXR1cm47XG4gICAgICAgIH1cblxuICAgICAgICB0aGlzLmVsZW1lbnQub3BlbiA9IGZhbHNlO1xuICAgICAgICAvLyBGb2t1cyB6dXLDvGNrIGF1ZiBkZW4gQXVzbMO2c2VyLCBzb25zdCBsYW5kZXQgZXIgaW0gTmlyZ2VuZHdvLlxuICAgICAgICB0aGlzLmVsZW1lbnQucXVlcnlTZWxlY3Rvcignc3VtbWFyeScpPy5mb2N1cygpO1xuICAgIH07XG5cbiAgICBjb25uZWN0KCk6IHZvaWQge1xuICAgICAgICBkb2N1bWVudC5hZGRFdmVudExpc3RlbmVyKCdjbGljaycsIHRoaXMub25PdXRzaWRlQ2xpY2spO1xuICAgICAgICBkb2N1bWVudC5hZGRFdmVudExpc3RlbmVyKCdrZXlkb3duJywgdGhpcy5vbktleWRvd24pO1xuICAgIH1cblxuICAgIGRpc2Nvbm5lY3QoKTogdm9pZCB7XG4gICAgICAgIGRvY3VtZW50LnJlbW92ZUV2ZW50TGlzdGVuZXIoJ2NsaWNrJywgdGhpcy5vbk91dHNpZGVDbGljayk7XG4gICAgICAgIGRvY3VtZW50LnJlbW92ZUV2ZW50TGlzdGVuZXIoJ2tleWRvd24nLCB0aGlzLm9uS2V5ZG93bik7XG4gICAgfVxufVxuIiwiaW1wb3J0IHsgQ29udHJvbGxlciB9IGZyb20gJ0Bob3R3aXJlZC9zdGltdWx1cyc7XG5cbi8qXG4gKiBTdGltdWx1cy1Db250cm9sbGVyIGbDvHIgZGllIG5hY2ggV29jaGVudGFnIGdydXBwaWVydGVuIMOWZmZudW5nc3plaXRlbi1TbG90cy5cbiAqIEVybGF1YnQgZGFzIEhpbnp1ZsO8Z2VuIG1laHJlcmVyIFplaXRzbG90cyBwcm8gVGFnICh6LiBCLiBNaXR0YWcgKyBBYmVuZClcbiAqIHVuZCBkYXMgRW50ZmVybmVuIGVpbnplbG5lciBTbG90cy4gTnV0enQgZWluZSBmbGFjaGUgU3ltZm9ueS1Db2xsZWN0aW9uVHlwZSxcbiAqIGRlc2hhbGIgd2lyZCBlaW4gZ2VtZWluc2FtZXIsIMO8YmVyIGFsbGUgVGFnZSBlaW5kZXV0aWdlciBJbmRleCBnZWbDvGhydC5cbiAqL1xuZXhwb3J0IGRlZmF1bHQgY2xhc3MgZXh0ZW5kcyBDb250cm9sbGVyIHtcbiAgICBzdGF0aWMgdmFsdWVzID0geyBwcm90b3R5cGU6IFN0cmluZyB9O1xuXG4gICAgZGVjbGFyZSBwcm90b3R5cGVWYWx1ZTogc3RyaW5nO1xuXG4gICAgI2luZGV4ITogbnVtYmVyO1xuXG4gICAgY29ubmVjdCgpOiB2b2lkIHtcbiAgICAgICAgdGhpcy4jaW5kZXggPSB0aGlzLmVsZW1lbnQucXVlcnlTZWxlY3RvckFsbCgnW2RhdGEtb3BlbmluZy1ob3Vycy1mb3JtLXRhcmdldD1cInNsb3RcIl0nKS5sZW5ndGg7XG4gICAgfVxuXG4gICAgYWRkU2xvdChldmVudDogRXZlbnQpOiB2b2lkIHtcbiAgICAgICAgY29uc3QgYnV0dG9uID0gZXZlbnQuY3VycmVudFRhcmdldCBhcyBIVE1MRWxlbWVudDtcbiAgICAgICAgY29uc3QgZGF5ID0gYnV0dG9uLmRhdGFzZXQub3BlbmluZ0hvdXJzRm9ybURheVBhcmFtO1xuICAgICAgICBpZiAoIWRheSkge1xuICAgICAgICAgICAgcmV0dXJuO1xuICAgICAgICB9XG5cbiAgICAgICAgY29uc3QgY29udGFpbmVyID0gdGhpcy5lbGVtZW50LnF1ZXJ5U2VsZWN0b3I8SFRNTEVsZW1lbnQ+KGBbZGF0YS1kYXk9XCIke2RheX1cIl1gKTtcbiAgICAgICAgaWYgKCFjb250YWluZXIpIHtcbiAgICAgICAgICAgIHJldHVybjtcbiAgICAgICAgfVxuXG4gICAgICAgIGNvbnN0IGh0bWwgPSB0aGlzLnByb3RvdHlwZVZhbHVlLnJlcGxhY2UoL19fbmFtZV9fL2csIFN0cmluZyh0aGlzLiNpbmRleCkpO1xuICAgICAgICB0aGlzLiNpbmRleCsrO1xuXG4gICAgICAgIGNvbnN0IHdyYXBwZXIgPSBkb2N1bWVudC5jcmVhdGVFbGVtZW50KCdkaXYnKTtcbiAgICAgICAgd3JhcHBlci5jbGFzc0xpc3QuYWRkKCdmbGV4JywgJ2l0ZW1zLWNlbnRlcicsICdnYXAtMicpO1xuICAgICAgICB3cmFwcGVyLnNldEF0dHJpYnV0ZSgnZGF0YS1vcGVuaW5nLWhvdXJzLWZvcm0tdGFyZ2V0JywgJ3Nsb3QnKTtcbiAgICAgICAgd3JhcHBlci5pbm5lckhUTUwgPSBodG1sICtcbiAgICAgICAgICAgICc8YnV0dG9uIHR5cGU9XCJidXR0b25cIiBkYXRhLWFjdGlvbj1cIm9wZW5pbmctaG91cnMtZm9ybSNyZW1vdmVTbG90XCIgJyArXG4gICAgICAgICAgICAnY2xhc3M9XCJ0ZXh0LXJlZC01MDAgaG92ZXI6dGV4dC1yZWQtNzAwIHRleHQtc20gZm9udC1ib2xkIHB4LTIgcHktMSBzaHJpbmstMCB0cmFuc2l0aW9uXCI+JyArXG4gICAgICAgICAgICAn4pyVPC9idXR0b24+JztcblxuICAgICAgICAvLyBEZW4gdmVyc3RlY2t0ZW4gZGF5T2ZXZWVrLUlucHV0IGRlcyBuZXVlbiBTbG90cyBhdWYgZGVuIFppZWx0YWcgc2V0emVuLlxuICAgICAgICBjb25zdCBkYXlJbnB1dCA9IHdyYXBwZXIucXVlcnlTZWxlY3RvcjxIVE1MSW5wdXRFbGVtZW50PignaW5wdXRbdHlwZT1cImhpZGRlblwiXVtuYW1lKj1cIltkYXlPZldlZWtdXCJdJyk7XG4gICAgICAgIGlmIChkYXlJbnB1dCkge1xuICAgICAgICAgICAgZGF5SW5wdXQudmFsdWUgPSBkYXk7XG4gICAgICAgIH1cblxuICAgICAgICBjb250YWluZXIuYXBwZW5kQ2hpbGQod3JhcHBlcik7XG4gICAgfVxuXG4gICAgcmVtb3ZlU2xvdChldmVudDogRXZlbnQpOiB2b2lkIHtcbiAgICAgICAgY29uc3QgdGFyZ2V0ID0gZXZlbnQudGFyZ2V0IGFzIEhUTUxFbGVtZW50O1xuICAgICAgICBjb25zdCBzbG90ID0gdGFyZ2V0LmNsb3Nlc3QoJ1tkYXRhLW9wZW5pbmctaG91cnMtZm9ybS10YXJnZXQ9XCJzbG90XCJdJyk7XG4gICAgICAgIGlmIChzbG90KSB7XG4gICAgICAgICAgICBzbG90LnJlbW92ZSgpO1xuICAgICAgICB9XG4gICAgfVxufVxuIiwiaW1wb3J0IHsgQ29udHJvbGxlciB9IGZyb20gJ0Bob3R3aXJlZC9zdGltdWx1cyc7XG5cbi8qKlxuICogQmxlbmRldCBkaWUgdHlwc3BlemlmaXNjaGVuIEZvcm11bGFyYmzDtmNrZSBwYXNzZW5kIHp1bSBnZXfDpGhsdGVuXG4gKiBPcmdhbmlzYXRpb25zdHlwIGVpbiB1bmQgYXVzLlxuICpcbiAqIFJlaW4genVzw6R0emxpY2g6IE9obmUgSmF2YVNjcmlwdCByZW5kZXJ0IGRlciBGb3JtVHlwZSBhbGxlIGRyZWkgQmzDtmNrZSwgdW5kXG4gKiBQUkVfU1VCTUlUIHZlcndpcmZ0IHNlcnZlcnNlaXRpZyBkaWUgRmVsZGVyIGRlciBuaWNodCBnZXfDpGhsdGVuIFR5cGVuLiBEZXJcbiAqIENvbnRyb2xsZXIgw6RuZGVydCBhbHNvIG51ciwgd2FzIHNpY2h0YmFyIGlzdCDigJMgbmllLCB3YXMgZ8O8bHRpZyBpc3QuXG4gKlxuICogRGVyIFdlY2hzZWwgd2lyZCBpbiBlaW5lciBMaXZlLVJlZ2lvbiBhbmdlc2FndCwgc29uc3QgYmVrb21tZW5cbiAqIFNjcmVlbnJlYWRlci1OdXR6ZXIgbmljaHQgbWl0LCBkYXNzIHNpY2ggZGFzIEZvcm11bGFyIHZlcsOkbmRlcnQgaGF0LlxuICovXG5leHBvcnQgZGVmYXVsdCBjbGFzcyBleHRlbmRzIENvbnRyb2xsZXI8SFRNTEVsZW1lbnQ+IHtcbiAgICBzdGF0aWMgdGFyZ2V0cyA9IFsnYmxvY2snLCAnYW5ub3VuY2VyJ107XG4gICAgc3RhdGljIHZhbHVlcyA9IHsgYW5ub3VuY2VtZW50OiBTdHJpbmcgfTtcblxuICAgIGRlY2xhcmUgcmVhZG9ubHkgYmxvY2tUYXJnZXRzOiBIVE1MRWxlbWVudFtdO1xuICAgIGRlY2xhcmUgcmVhZG9ubHkgYW5ub3VuY2VyVGFyZ2V0OiBIVE1MRWxlbWVudDtcbiAgICBkZWNsYXJlIHJlYWRvbmx5IGhhc0Fubm91bmNlclRhcmdldDogYm9vbGVhbjtcbiAgICBkZWNsYXJlIGFubm91bmNlbWVudFZhbHVlOiBzdHJpbmc7XG5cbiAgICBjb25uZWN0KCk6IHZvaWQge1xuICAgICAgICB0aGlzLnVwZGF0ZShmYWxzZSk7XG4gICAgfVxuXG4gICAgY2hhbmdlKCk6IHZvaWQge1xuICAgICAgICB0aGlzLnVwZGF0ZSh0cnVlKTtcbiAgICB9XG5cbiAgICBwcml2YXRlIHVwZGF0ZShhbm5vdW5jZTogYm9vbGVhbik6IHZvaWQge1xuICAgICAgICBjb25zdCBzZWxlY3RlZCA9IHRoaXMuc2VsZWN0ZWRUeXBlKCk7XG5cbiAgICAgICAgdGhpcy5ibG9ja1RhcmdldHMuZm9yRWFjaCgoYmxvY2spID0+IHtcbiAgICAgICAgICAgIGNvbnN0IG1hdGNoZXMgPSBibG9jay5kYXRhc2V0LnR5cGUgPT09IHNlbGVjdGVkO1xuICAgICAgICAgICAgYmxvY2suaGlkZGVuID0gIW1hdGNoZXM7XG5cbiAgICAgICAgICAgIC8vIEZlbGRlciBkZXMgbmljaHQgZ2V3w6RobHRlbiBUeXBzIGF1cyBkZXIgVGFiLVJlaWhlbmZvbGdlIG5laG1lbiDigJNcbiAgICAgICAgICAgIC8vIGBoaWRkZW5gIGFsbGVpbiBnZW7DvGd0IGJlaSBtYW5jaGVuIEtvbWJpbmF0aW9uZW4gbmljaHQuXG4gICAgICAgICAgICBibG9jay5xdWVyeVNlbGVjdG9yQWxsPEhUTUxJbnB1dEVsZW1lbnQgfCBIVE1MU2VsZWN0RWxlbWVudCB8IEhUTUxUZXh0QXJlYUVsZW1lbnQ+KFxuICAgICAgICAgICAgICAgICdpbnB1dCwgc2VsZWN0LCB0ZXh0YXJlYScsXG4gICAgICAgICAgICApLmZvckVhY2goKGZpZWxkKSA9PiB7XG4gICAgICAgICAgICAgICAgZmllbGQuZGlzYWJsZWQgPSAhbWF0Y2hlcztcbiAgICAgICAgICAgIH0pO1xuICAgICAgICB9KTtcblxuICAgICAgICBpZiAoYW5ub3VuY2UgJiYgc2VsZWN0ZWQpIHtcbiAgICAgICAgICAgIHRoaXMuYW5ub3VuY2Uoc2VsZWN0ZWQpO1xuICAgICAgICB9XG4gICAgfVxuXG4gICAgcHJpdmF0ZSBzZWxlY3RlZFR5cGUoKTogc3RyaW5nIHwgbnVsbCB7XG4gICAgICAgIGNvbnN0IGNoZWNrZWQgPSB0aGlzLmVsZW1lbnQucXVlcnlTZWxlY3RvcjxIVE1MSW5wdXRFbGVtZW50PignaW5wdXRbdHlwZT1cInJhZGlvXCJdOmNoZWNrZWQnKTtcblxuICAgICAgICByZXR1cm4gY2hlY2tlZCA/IGNoZWNrZWQudmFsdWUgOiBudWxsO1xuICAgIH1cblxuICAgIHByaXZhdGUgYW5ub3VuY2UodHlwZTogc3RyaW5nKTogdm9pZCB7XG4gICAgICAgIGlmICghdGhpcy5oYXNBbm5vdW5jZXJUYXJnZXQpIHtcbiAgICAgICAgICAgIHJldHVybjtcbiAgICAgICAgfVxuXG4gICAgICAgIGNvbnN0IGJsb2NrID0gdGhpcy5ibG9ja1RhcmdldHMuZmluZCgoYikgPT4gYi5kYXRhc2V0LnR5cGUgPT09IHR5cGUpO1xuICAgICAgICBjb25zdCBsYWJlbCA9IGJsb2NrPy5kYXRhc2V0LmxhYmVsID8/ICcnO1xuXG4gICAgICAgIC8vIEt1cnogbGVlcmVuLCBkYW1pdCBhdWNoIGVpbmUgd2llZGVyaG9sdGUgQXVzd2FobCBuZXUgdm9yZ2VsZXNlbiB3aXJkLlxuICAgICAgICB0aGlzLmFubm91bmNlclRhcmdldC50ZXh0Q29udGVudCA9ICcnO1xuICAgICAgICB3aW5kb3cuc2V0VGltZW91dCgoKSA9PiB7XG4gICAgICAgICAgICB0aGlzLmFubm91bmNlclRhcmdldC50ZXh0Q29udGVudCA9IHRoaXMuYW5ub3VuY2VtZW50VmFsdWUucmVwbGFjZSgnJXR5cGUlJywgbGFiZWwpO1xuICAgICAgICB9LCA1MCk7XG4gICAgfVxufVxuIiwiaW1wb3J0IHsgQ29udHJvbGxlciB9IGZyb20gJ0Bob3R3aXJlZC9zdGltdWx1cyc7XG5cbi8qKlxuICogU2ljaHRiYXJrZWl0LCBMYWRlenVzdGFuZCB1bmQgdmVyc3TDpG5kbGljaGUgRmVobGVybWVsZHVuZ2VuIHJ1bmQgdW0gUGFzc2tleXMuXG4gKlxuICogRGVuIFdlYkF1dGhuLUFibGF1ZiBzZWxic3Qgw7xiZXJuZWhtZW4gZGllIGJlaWRlbiBDb250cm9sbGVyIGF1c1xuICogYEB3ZWItYXV0aC93ZWJhdXRobi1zdGltdWx1c2AgKHJlZ2lzdHJpZXJ0IGluIHN0aW11bHVzX2Jvb3RzdHJhcC50cyBhbHNcbiAqIGBwYXNza2V5LWF1dGhgIHVuZCBgcGFzc2tleS1yZWdpc3RlcmApLiBEaWUgbWVsZGVuIGlocmVuIEZvcnRzY2hyaXR0IMO8YmVyXG4gKiBhdWZzdGVpZ2VuZGUgQ3VzdG9tRXZlbnRzIOKAkyBkaWVzZXIgQ29udHJvbGxlciBow7ZydCBkYXJhdWYgdW5kIG1hY2h0IGRhcmF1c1xuICogZGFzLCB3YXMgZGFzIEZyZW1kcGFrZXQgbmljaHQgbGllZmVybiBrYW5uOiDDvGJlcnNldHp0ZW4gVGV4dCB1bmQgZWluZW5cbiAqIEtub3BmLCBkZXIgZXJzdCBlcnNjaGVpbnQsIHdlbm4gZGVyIEJyb3dzZXIgw7xiZXJoYXVwdCBQYXNza2V5cyBiZWhlcnJzY2h0LlxuICpcbiAqIERpZSBNZWxkdW5nZW4ga29tbWVuIGFscyBWYWx1ZXMgYXVzIGRlbSBUZW1wbGF0ZSwgd2VpbCBkaWUgw5xiZXJzZXR6dW5nIGRvcnRcbiAqIGhpbmdlaMO2cnQgdW5kIG5pY2h0IGluIGVpbmUgSmF2YVNjcmlwdC1EYXRlaS5cbiAqL1xuZXhwb3J0IGRlZmF1bHQgY2xhc3MgZXh0ZW5kcyBDb250cm9sbGVyIHtcbiAgICBzdGF0aWMgdGFyZ2V0cyA9IFsncGFuZWwnLCAnYnV0dG9uJywgJ21lc3NhZ2UnXTtcblxuICAgIHN0YXRpYyB2YWx1ZXMgPSB7XG4gICAgICAgIHVuc3VwcG9ydGVkOiBTdHJpbmcsXG4gICAgICAgIGZhaWxlZDogU3RyaW5nLFxuICAgICAgICBzZXJ2ZXI6IFN0cmluZyxcbiAgICAgICAgZXhpc3RzOiBTdHJpbmcsXG4gICAgICAgIGNvbmZpZzogU3RyaW5nLFxuICAgICAgICBidXN5OiBTdHJpbmcsXG4gICAgfTtcblxuICAgIGRlY2xhcmUgcmVhZG9ubHkgcGFuZWxUYXJnZXQ6IEhUTUxFbGVtZW50O1xuICAgIGRlY2xhcmUgcmVhZG9ubHkgaGFzUGFuZWxUYXJnZXQ6IGJvb2xlYW47XG4gICAgZGVjbGFyZSByZWFkb25seSBidXR0b25UYXJnZXQ6IEhUTUxCdXR0b25FbGVtZW50O1xuICAgIGRlY2xhcmUgcmVhZG9ubHkgaGFzQnV0dG9uVGFyZ2V0OiBib29sZWFuO1xuICAgIGRlY2xhcmUgcmVhZG9ubHkgbWVzc2FnZVRhcmdldDogSFRNTEVsZW1lbnQ7XG4gICAgZGVjbGFyZSByZWFkb25seSBoYXNNZXNzYWdlVGFyZ2V0OiBib29sZWFuO1xuICAgIGRlY2xhcmUgdW5zdXBwb3J0ZWRWYWx1ZTogc3RyaW5nO1xuICAgIGRlY2xhcmUgZmFpbGVkVmFsdWU6IHN0cmluZztcbiAgICBkZWNsYXJlIHNlcnZlclZhbHVlOiBzdHJpbmc7XG4gICAgZGVjbGFyZSBleGlzdHNWYWx1ZTogc3RyaW5nO1xuICAgIGRlY2xhcmUgY29uZmlnVmFsdWU6IHN0cmluZztcbiAgICBkZWNsYXJlIGJ1c3lWYWx1ZTogc3RyaW5nO1xuXG4gICAgcHJpdmF0ZSBpZGxlTGFiZWwgPSAnJztcblxuICAgIGNvbm5lY3QoKTogdm9pZCB7XG4gICAgICAgIC8vIE9obmUgV2ViQXV0aG4gaW0gQnJvd3NlciBibGVpYnQgZGVyIEtub3BmIHZlcmJvcmdlbjogRWluIEFuZ2Vib3QsIGRhc1xuICAgICAgICAvLyBiZWltIEFudGlwcGVuIG51ciBlaW5lIEZlaGxlcm1lbGR1bmcgbGllZmVydCwgaXN0IHNjaGxlY2h0ZXIgYWxzXG4gICAgICAgIC8vIGtlaW5lcy4gRGVyIFBhc3N3b3J0LUxvZ2luIHN0ZWh0IG9obmVoaW4gZGFuZWJlbi5cbiAgICAgICAgaWYgKHRoaXMuaGFzUGFuZWxUYXJnZXQgJiYgdGhpcy4jYnJvd3NlclN1cHBvcnRzUGFzc2tleXMoKSkge1xuICAgICAgICAgICAgdGhpcy5wYW5lbFRhcmdldC5jbGFzc0xpc3QucmVtb3ZlKCdoaWRkZW4nKTtcbiAgICAgICAgfVxuXG4gICAgICAgIGlmICh0aGlzLmhhc0J1dHRvblRhcmdldCkge1xuICAgICAgICAgICAgdGhpcy5pZGxlTGFiZWwgPSB0aGlzLmJ1dHRvblRhcmdldC50ZXh0Q29udGVudCA/PyAnJztcbiAgICAgICAgfVxuICAgIH1cblxuICAgIC8vIERlciBBYmxhdWYgaGF0IGJlZ29ubmVuIOKAkyBhYiBoaWVyIHdhcnRldCBkZXIgQnJvd3NlciBhdWYgRmFjZSBJRCwgVG91Y2ggSUQgb2RlciBQSU4uXG4gICAgc3RhcnQoKTogdm9pZCB7XG4gICAgICAgIHRoaXMuI2NsZWFyTWVzc2FnZSgpO1xuXG4gICAgICAgIGlmICh0aGlzLmhhc0J1dHRvblRhcmdldCkge1xuICAgICAgICAgICAgdGhpcy5idXR0b25UYXJnZXQuZGlzYWJsZWQgPSB0cnVlO1xuICAgICAgICAgICAgdGhpcy5idXR0b25UYXJnZXQuc2V0QXR0cmlidXRlKCdhcmlhLWJ1c3knLCAndHJ1ZScpO1xuXG4gICAgICAgICAgICBpZiAodGhpcy5idXN5VmFsdWUgIT09ICcnKSB7XG4gICAgICAgICAgICAgICAgdGhpcy5idXR0b25UYXJnZXQudGV4dENvbnRlbnQgPSB0aGlzLmJ1c3lWYWx1ZTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfVxuICAgIH1cblxuICAgIHVuc3VwcG9ydGVkKCk6IHZvaWQge1xuICAgICAgICB0aGlzLiNyZXNldCgpO1xuICAgICAgICB0aGlzLiNzaG93TWVzc2FnZSh0aGlzLnVuc3VwcG9ydGVkVmFsdWUpO1xuICAgIH1cblxuICAgIC8qKlxuICAgICAqIEZlaGxlciBhdXMgZGVtIENlcmVtb255LVRlaWwgKG5hdmlnYXRvci5jcmVkZW50aWFscykuXG4gICAgICovXG4gICAgY2VyZW1vbnlFcnJvcihldmVudDogQ3VzdG9tRXZlbnQ8eyBjb2RlPzogc3RyaW5nOyBuYW1lPzogc3RyaW5nIH0+KTogdm9pZCB7XG4gICAgICAgIHRoaXMuI3Jlc2V0KCk7XG5cbiAgICAgICAgY29uc3QgY29kZSA9IGV2ZW50LmRldGFpbD8uY29kZTtcblxuICAgICAgICAvLyBBYmJydWNoIGR1cmNoIGRlbiBOdXR6ZXIgb2RlciBhYmdlbGF1ZmVuZXMgWmVpdGZlbnN0ZXIuIERhcyBpc3Qga2VpblxuICAgICAgICAvLyBGZWhsZXIsIHNvbmRlcm4gZWluZSBFbnRzY2hlaWR1bmcg4oCTIGRhZsO8ciBnaWJ0IGVzIGtlaW5lIE1lbGR1bmcuXG4gICAgICAgIGlmIChjb2RlID09PSAnRVJST1JfQ0VSRU1PTllfQUJPUlRFRCcpIHtcbiAgICAgICAgICAgIHJldHVybjtcbiAgICAgICAgfVxuXG4gICAgICAgIGlmIChjb2RlID09PSAnRVJST1JfQVVUSEVOVElDQVRPUl9QUkVWSU9VU0xZX1JFR0lTVEVSRUQnKSB7XG4gICAgICAgICAgICB0aGlzLiNzaG93TWVzc2FnZSh0aGlzLmV4aXN0c1ZhbHVlKTtcblxuICAgICAgICAgICAgcmV0dXJuO1xuICAgICAgICB9XG5cbiAgICAgICAgLy8gRGllIERvbWFpbiBwYXNzdCBuaWNodCB6dXIga29uZmlndXJpZXJ0ZW4gcmVseWluZyBwYXJ0eSBpZC4gQmV0cmlmZnRcbiAgICAgICAgLy8gbmllIGRlbiBOdXR6ZXIsIHNvbmRlcm4gaW1tZXIgZGllIEVpbnJpY2h0dW5nIOKAkyBkZXNoYWxiIGVpbiBlaWdlbmVyXG4gICAgICAgIC8vIFRleHQgc3RhdHQgZGVyIGFsbGdlbWVpbmVuIEZlaGxlcm1lbGR1bmcuXG4gICAgICAgIGlmIChjb2RlID09PSAnRVJST1JfSU5WQUxJRF9ET01BSU4nIHx8IGNvZGUgPT09ICdFUlJPUl9JTlZBTElEX1JQX0lEJykge1xuICAgICAgICAgICAgdGhpcy4jc2hvd01lc3NhZ2UodGhpcy5jb25maWdWYWx1ZSk7XG5cbiAgICAgICAgICAgIHJldHVybjtcbiAgICAgICAgfVxuXG4gICAgICAgIHRoaXMuI3Nob3dNZXNzYWdlKHRoaXMuZmFpbGVkVmFsdWUpO1xuICAgIH1cblxuICAgIC8qKlxuICAgICAqIEZlaGxlciBhdWYgZGVtIFdlZyB6dW0gb2RlciB2b20gU2VydmVyLlxuICAgICAqL1xuICAgIHNlcnZlckVycm9yKCk6IHZvaWQge1xuICAgICAgICB0aGlzLiNyZXNldCgpO1xuICAgICAgICB0aGlzLiNzaG93TWVzc2FnZSh0aGlzLnNlcnZlclZhbHVlKTtcbiAgICB9XG5cbiAgICAjYnJvd3NlclN1cHBvcnRzUGFzc2tleXMoKTogYm9vbGVhbiB7XG4gICAgICAgIHJldHVybiB0eXBlb2Ygd2luZG93LlB1YmxpY0tleUNyZWRlbnRpYWwgPT09ICdmdW5jdGlvbic7XG4gICAgfVxuXG4gICAgI3Jlc2V0KCk6IHZvaWQge1xuICAgICAgICBpZiAodGhpcy5oYXNCdXR0b25UYXJnZXQpIHtcbiAgICAgICAgICAgIHRoaXMuYnV0dG9uVGFyZ2V0LmRpc2FibGVkID0gZmFsc2U7XG4gICAgICAgICAgICB0aGlzLmJ1dHRvblRhcmdldC5yZW1vdmVBdHRyaWJ1dGUoJ2FyaWEtYnVzeScpO1xuICAgICAgICAgICAgdGhpcy5idXR0b25UYXJnZXQudGV4dENvbnRlbnQgPSB0aGlzLmlkbGVMYWJlbDtcbiAgICAgICAgfVxuICAgIH1cblxuICAgICNzaG93TWVzc2FnZSh0ZXh0OiBzdHJpbmcpOiB2b2lkIHtcbiAgICAgICAgaWYgKHRoaXMuaGFzTWVzc2FnZVRhcmdldCAmJiB0ZXh0ICE9PSAnJykge1xuICAgICAgICAgICAgLy8gRXJzdCBzaWNodGJhciBtYWNoZW4sIGRhbm4gYmVzY2hyaWZ0ZW46IEVpbiByb2xlPVwiYWxlcnRcIiBtZWxkZXRcbiAgICAgICAgICAgIC8vIG51ciDDhG5kZXJ1bmdlbiwgZGllIGluIGVpbmVtIGJlcmVpdHMgZGFyZ2VzdGVsbHRlbiBCZXJlaWNoXG4gICAgICAgICAgICAvLyBwYXNzaWVyZW4uIEFuZGVyc2hlcnVtIHZlcnNjaGx1Y2tlbiBtYW5jaGUgU2NyZWVucmVhZGVyIGRpZVxuICAgICAgICAgICAgLy8gQW5zYWdlLlxuICAgICAgICAgICAgdGhpcy5tZXNzYWdlVGFyZ2V0LmNsYXNzTGlzdC5yZW1vdmUoJ2hpZGRlbicpO1xuICAgICAgICAgICAgdGhpcy5tZXNzYWdlVGFyZ2V0LnRleHRDb250ZW50ID0gdGV4dDtcbiAgICAgICAgfVxuICAgIH1cblxuICAgICNjbGVhck1lc3NhZ2UoKTogdm9pZCB7XG4gICAgICAgIGlmICh0aGlzLmhhc01lc3NhZ2VUYXJnZXQpIHtcbiAgICAgICAgICAgIHRoaXMubWVzc2FnZVRhcmdldC50ZXh0Q29udGVudCA9ICcnO1xuICAgICAgICAgICAgdGhpcy5tZXNzYWdlVGFyZ2V0LmNsYXNzTGlzdC5hZGQoJ2hpZGRlbicpO1xuICAgICAgICB9XG4gICAgfVxufVxuIiwiaW1wb3J0IHsgQ29udHJvbGxlciB9IGZyb20gJ0Bob3R3aXJlZC9zdGltdWx1cyc7XG5cbi8vIE1hcmtpZXJ1bmcgZsO8ciB1bmJlYW50d29ydGV0ZSBQZmxpY2h0ZnJhZ2VuXG5jb25zdCBNSVNTSU5HX0NMQVNTRVMgPSBbJ3JpbmctMicsICdyaW5nLXJlZC00MDAnLCAncmluZy1vZmZzZXQtMicsICdwLTInLCAnLW0tMiddO1xuXG5leHBvcnQgZGVmYXVsdCBjbGFzcyBleHRlbmRzIENvbnRyb2xsZXIge1xuICAgIHN0YXRpYyB0YXJnZXRzID0gWydzdGVwJywgJ2luZGljYXRvcicsICdwcmV2QnV0dG9uJywgJ25leHRCdXR0b24nLCAnc3VibWl0QnV0dG9uJywgJ2Vycm9yJywgJ2Fubm91bmNlciddO1xuICAgIHN0YXRpYyB2YWx1ZXMgPSB7XG4gICAgICAgIGN1cnJlbnQ6IHsgdHlwZTogTnVtYmVyLCBkZWZhdWx0OiAxIH0sXG4gICAgICAgIHRvdGFsOiBOdW1iZXIsXG4gICAgICAgIGluY29tcGxldGVNZXNzYWdlOiBTdHJpbmcsXG4gICAgICAgIGFubm91bmNlVGVtcGxhdGU6IFN0cmluZyxcbiAgICB9O1xuXG4gICAgZGVjbGFyZSBjdXJyZW50VmFsdWU6IG51bWJlcjtcbiAgICBkZWNsYXJlIHRvdGFsVmFsdWU6IG51bWJlcjtcbiAgICBkZWNsYXJlIGluY29tcGxldGVNZXNzYWdlVmFsdWU6IHN0cmluZztcbiAgICBkZWNsYXJlIGFubm91bmNlVGVtcGxhdGVWYWx1ZTogc3RyaW5nO1xuICAgIGRlY2xhcmUgcmVhZG9ubHkgc3RlcFRhcmdldHM6IEhUTUxFbGVtZW50W107XG4gICAgZGVjbGFyZSByZWFkb25seSBpbmRpY2F0b3JUYXJnZXRzOiBIVE1MRWxlbWVudFtdO1xuICAgIGRlY2xhcmUgcmVhZG9ubHkgcHJldkJ1dHRvblRhcmdldDogSFRNTEVsZW1lbnQ7XG4gICAgZGVjbGFyZSByZWFkb25seSBuZXh0QnV0dG9uVGFyZ2V0OiBIVE1MRWxlbWVudDtcbiAgICBkZWNsYXJlIHJlYWRvbmx5IHN1Ym1pdEJ1dHRvblRhcmdldDogSFRNTEVsZW1lbnQ7XG4gICAgZGVjbGFyZSByZWFkb25seSBlcnJvclRhcmdldDogSFRNTEVsZW1lbnQ7XG4gICAgZGVjbGFyZSByZWFkb25seSBoYXNFcnJvclRhcmdldDogYm9vbGVhbjtcbiAgICBkZWNsYXJlIHJlYWRvbmx5IGFubm91bmNlclRhcmdldDogSFRNTEVsZW1lbnQ7XG4gICAgZGVjbGFyZSByZWFkb25seSBoYXNBbm5vdW5jZXJUYXJnZXQ6IGJvb2xlYW47XG5cbiAgICBjb25uZWN0KCk6IHZvaWQge1xuICAgICAgICB0aGlzLnVwZGF0ZVZpZXcoKTtcbiAgICB9XG5cbiAgICBuZXh0KCk6IHZvaWQge1xuICAgICAgICBpZiAoIXRoaXMudmFsaWRhdGVTdGVwKCkpIHtcbiAgICAgICAgICAgIHJldHVybjtcbiAgICAgICAgfVxuXG4gICAgICAgIGlmICh0aGlzLmN1cnJlbnRWYWx1ZSA8IHRoaXMudG90YWxWYWx1ZSkge1xuICAgICAgICAgICAgdGhpcy5jdXJyZW50VmFsdWUrKztcbiAgICAgICAgICAgIHRoaXMudXBkYXRlVmlldyh0cnVlKTtcbiAgICAgICAgfVxuICAgIH1cblxuICAgIHByZXYoKTogdm9pZCB7XG4gICAgICAgIGlmICh0aGlzLmN1cnJlbnRWYWx1ZSA+IDEpIHtcbiAgICAgICAgICAgIHRoaXMuY3VycmVudFZhbHVlLS07XG4gICAgICAgICAgICB0aGlzLnVwZGF0ZVZpZXcodHJ1ZSk7XG4gICAgICAgIH1cbiAgICB9XG5cbiAgICBnb1RvKGV2ZW50OiBFdmVudCk6IHZvaWQge1xuICAgICAgICBjb25zdCB0YXJnZXQgPSBldmVudC5jdXJyZW50VGFyZ2V0IGFzIEhUTUxFbGVtZW50O1xuICAgICAgICBjb25zdCBzdGVwID0gcGFyc2VJbnQodGFyZ2V0LmRhdGFzZXQuc3RlcCB8fCAnMScsIDEwKTtcbiAgICAgICAgaWYgKHN0ZXAgPj0gMSAmJiBzdGVwIDw9IHRoaXMudG90YWxWYWx1ZSkge1xuICAgICAgICAgICAgdGhpcy5jdXJyZW50VmFsdWUgPSBzdGVwO1xuICAgICAgICAgICAgdGhpcy51cGRhdGVWaWV3KHRydWUpO1xuICAgICAgICB9XG4gICAgfVxuXG4gICAgLyoqXG4gICAgICogUHLDvGZ0LCBvYiBpbSBha3R1ZWxsZW4gU3RlcCBhbGxlIGRyZWl3ZXJ0aWdlbiBQZmxpY2h0ZnJhZ2VuIGJlYW50d29ydGV0IHNpbmQuXG4gICAgICogUmVpbmUgVVgtSGlsZmUg4oCTIGRpZSBlaWdlbnRsaWNoZSBBYnNpY2hlcnVuZyBpc3QgZGVyIE5vdE51bGwtQ29uc3RyYWludCBpbSBGb3JtLVR5cGUuXG4gICAgICovXG4gICAgcHJpdmF0ZSB2YWxpZGF0ZVN0ZXAoKTogYm9vbGVhbiB7XG4gICAgICAgIGNvbnN0IHN0ZXAgPSB0aGlzLnN0ZXBUYXJnZXRzW3RoaXMuY3VycmVudFZhbHVlIC0gMV07XG4gICAgICAgIGlmICghc3RlcCkge1xuICAgICAgICAgICAgcmV0dXJuIHRydWU7XG4gICAgICAgIH1cblxuICAgICAgICBjb25zdCBncm91cHMgPSBBcnJheS5mcm9tKHN0ZXAucXVlcnlTZWxlY3RvckFsbDxIVE1MRWxlbWVudD4oJ1tkYXRhLXRyaXN0YXRlXScpKTtcbiAgICAgICAgY29uc3QgaXNBbnN3ZXJlZCA9IChncm91cDogSFRNTEVsZW1lbnQpOiBib29sZWFuID0+XG4gICAgICAgICAgICBncm91cC5xdWVyeVNlbGVjdG9yKCdpbnB1dFt0eXBlPVwicmFkaW9cIl06Y2hlY2tlZCcpICE9PSBudWxsO1xuXG4gICAgICAgIGZvciAoY29uc3QgZ3JvdXAgb2YgZ3JvdXBzKSB7XG4gICAgICAgICAgICBjb25zdCBhbnN3ZXJlZCA9IGlzQW5zd2VyZWQoZ3JvdXApO1xuICAgICAgICAgICAgZ3JvdXAuY2xhc3NMaXN0W2Fuc3dlcmVkID8gJ3JlbW92ZScgOiAnYWRkJ10oLi4uTUlTU0lOR19DTEFTU0VTKTtcbiAgICAgICAgICAgIGdyb3VwLnNldEF0dHJpYnV0ZSgnYXJpYS1pbnZhbGlkJywgYW5zd2VyZWQgPyAnZmFsc2UnIDogJ3RydWUnKTtcbiAgICAgICAgfVxuXG4gICAgICAgIGNvbnN0IG1pc3NpbmcgPSBncm91cHMuZmluZCgoZ3JvdXApID0+ICFpc0Fuc3dlcmVkKGdyb3VwKSk7XG5cbiAgICAgICAgaWYgKCFtaXNzaW5nKSB7XG4gICAgICAgICAgICB0aGlzLmNsZWFyRXJyb3JzKCk7XG5cbiAgICAgICAgICAgIHJldHVybiB0cnVlO1xuICAgICAgICB9XG5cbiAgICAgICAgaWYgKHRoaXMuaGFzRXJyb3JUYXJnZXQpIHtcbiAgICAgICAgICAgIHRoaXMuZXJyb3JUYXJnZXQudGV4dENvbnRlbnQgPSB0aGlzLmluY29tcGxldGVNZXNzYWdlVmFsdWU7XG4gICAgICAgICAgICB0aGlzLmVycm9yVGFyZ2V0LmNsYXNzTGlzdC5yZW1vdmUoJ2hpZGRlbicpO1xuICAgICAgICB9XG5cbiAgICAgICAgbWlzc2luZy5zY3JvbGxJbnRvVmlldyh7IGJsb2NrOiAnY2VudGVyJywgYmVoYXZpb3I6ICdzbW9vdGgnIH0pO1xuICAgICAgICBtaXNzaW5nLnF1ZXJ5U2VsZWN0b3I8SFRNTElucHV0RWxlbWVudD4oJ2lucHV0W3R5cGU9XCJyYWRpb1wiXScpPy5mb2N1cyh7IHByZXZlbnRTY3JvbGw6IHRydWUgfSk7XG5cbiAgICAgICAgcmV0dXJuIGZhbHNlO1xuICAgIH1cblxuICAgIHByaXZhdGUgY2xlYXJFcnJvcnMoKTogdm9pZCB7XG4gICAgICAgIHRoaXMuZWxlbWVudC5xdWVyeVNlbGVjdG9yQWxsPEhUTUxFbGVtZW50PignW2RhdGEtdHJpc3RhdGVdJykuZm9yRWFjaCgoZ3JvdXApID0+IHtcbiAgICAgICAgICAgIGdyb3VwLmNsYXNzTGlzdC5yZW1vdmUoLi4uTUlTU0lOR19DTEFTU0VTKTtcbiAgICAgICAgICAgIGdyb3VwLnJlbW92ZUF0dHJpYnV0ZSgnYXJpYS1pbnZhbGlkJyk7XG4gICAgICAgIH0pO1xuXG4gICAgICAgIGlmICh0aGlzLmhhc0Vycm9yVGFyZ2V0KSB7XG4gICAgICAgICAgICB0aGlzLmVycm9yVGFyZ2V0LnRleHRDb250ZW50ID0gJyc7XG4gICAgICAgICAgICB0aGlzLmVycm9yVGFyZ2V0LmNsYXNzTGlzdC5hZGQoJ2hpZGRlbicpO1xuICAgICAgICB9XG4gICAgfVxuXG4gICAgcHJpdmF0ZSB1cGRhdGVWaWV3KGFubm91bmNlID0gZmFsc2UpOiB2b2lkIHtcbiAgICAgICAgdGhpcy5jbGVhckVycm9ycygpO1xuXG4gICAgICAgIC8vIFN0ZXBzIGVpbi0vYXVzYmxlbmRlblxuICAgICAgICB0aGlzLnN0ZXBUYXJnZXRzLmZvckVhY2goKGVsLCBpbmRleCkgPT4ge1xuICAgICAgICAgICAgZWwuY2xhc3NMaXN0LnRvZ2dsZSgnaGlkZGVuJywgaW5kZXggKyAxICE9PSB0aGlzLmN1cnJlbnRWYWx1ZSk7XG4gICAgICAgIH0pO1xuXG4gICAgICAgIC8vIFN0ZXAtSW5kaWthdG9yZW4gYWt0dWFsaXNpZXJlblxuICAgICAgICB0aGlzLmluZGljYXRvclRhcmdldHMuZm9yRWFjaCgoZWwsIGluZGV4KSA9PiB7XG4gICAgICAgICAgICBjb25zdCBzdGVwTnVtID0gaW5kZXggKyAxO1xuICAgICAgICAgICAgY29uc3QgY2lyY2xlID0gZWwucXVlcnlTZWxlY3RvcignW2RhdGEtY2lyY2xlXScpIGFzIEhUTUxFbGVtZW50O1xuICAgICAgICAgICAgY29uc3QgbGFiZWwgPSBlbC5xdWVyeVNlbGVjdG9yKCdbZGF0YS1sYWJlbF0nKSBhcyBIVE1MRWxlbWVudDtcbiAgICAgICAgICAgIGNvbnN0IGxpbmUgPSBlbC5xdWVyeVNlbGVjdG9yKCdbZGF0YS1saW5lXScpIGFzIEhUTUxFbGVtZW50O1xuXG4gICAgICAgICAgICBpZiAoY2lyY2xlKSB7XG4gICAgICAgICAgICAgICAgY2lyY2xlLmNsYXNzTGlzdC5yZW1vdmUoJ2JnLWN5YW4tNjAwJywgJ3RleHQtd2hpdGUnLCAnYmctZ3JlZW4tNTAwJywgJ2JnLWdyYXktMjAwJywgJ3RleHQtZ3JheS02MDAnKTtcbiAgICAgICAgICAgICAgICBpZiAoc3RlcE51bSA9PT0gdGhpcy5jdXJyZW50VmFsdWUpIHtcbiAgICAgICAgICAgICAgICAgICAgY2lyY2xlLmNsYXNzTGlzdC5hZGQoJ2JnLWN5YW4tNjAwJywgJ3RleHQtd2hpdGUnKTtcbiAgICAgICAgICAgICAgICB9IGVsc2UgaWYgKHN0ZXBOdW0gPCB0aGlzLmN1cnJlbnRWYWx1ZSkge1xuICAgICAgICAgICAgICAgICAgICBjaXJjbGUuY2xhc3NMaXN0LmFkZCgnYmctZ3JlZW4tNTAwJywgJ3RleHQtd2hpdGUnKTtcbiAgICAgICAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgICAgICAgICBjaXJjbGUuY2xhc3NMaXN0LmFkZCgnYmctZ3JheS0yMDAnLCAndGV4dC1ncmF5LTYwMCcpO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgaWYgKGxhYmVsKSB7XG4gICAgICAgICAgICAgICAgbGFiZWwuY2xhc3NMaXN0LnJlbW92ZSgndGV4dC1jeWFuLTcwMCcsICdmb250LXNlbWlib2xkJywgJ3RleHQtZ3JlZW4tNzAwJywgJ3RleHQtZ3JheS01MDAnKTtcbiAgICAgICAgICAgICAgICBpZiAoc3RlcE51bSA9PT0gdGhpcy5jdXJyZW50VmFsdWUpIHtcbiAgICAgICAgICAgICAgICAgICAgbGFiZWwuY2xhc3NMaXN0LmFkZCgndGV4dC1jeWFuLTcwMCcsICdmb250LXNlbWlib2xkJyk7XG4gICAgICAgICAgICAgICAgfSBlbHNlIGlmIChzdGVwTnVtIDwgdGhpcy5jdXJyZW50VmFsdWUpIHtcbiAgICAgICAgICAgICAgICAgICAgbGFiZWwuY2xhc3NMaXN0LmFkZCgndGV4dC1ncmVlbi03MDAnKTtcbiAgICAgICAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgICAgICAgICBsYWJlbC5jbGFzc0xpc3QuYWRkKCd0ZXh0LWdyYXktNTAwJyk7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICBpZiAobGluZSkge1xuICAgICAgICAgICAgICAgIGxpbmUuY2xhc3NMaXN0LnJlbW92ZSgnYmctZ3JlZW4tNTAwJywgJ2JnLWdyYXktMjAwJyk7XG4gICAgICAgICAgICAgICAgbGluZS5jbGFzc0xpc3QuYWRkKHN0ZXBOdW0gPCB0aGlzLmN1cnJlbnRWYWx1ZSA/ICdiZy1ncmVlbi01MDAnIDogJ2JnLWdyYXktMjAwJyk7XG4gICAgICAgICAgICB9XG4gICAgICAgIH0pO1xuXG4gICAgICAgIC8vIEJ1dHRvbnNcbiAgICAgICAgdGhpcy5wcmV2QnV0dG9uVGFyZ2V0LmNsYXNzTGlzdC50b2dnbGUoJ2hpZGRlbicsIHRoaXMuY3VycmVudFZhbHVlID09PSAxKTtcbiAgICAgICAgdGhpcy5uZXh0QnV0dG9uVGFyZ2V0LmNsYXNzTGlzdC50b2dnbGUoJ2hpZGRlbicsIHRoaXMuY3VycmVudFZhbHVlID09PSB0aGlzLnRvdGFsVmFsdWUpO1xuICAgICAgICB0aGlzLnN1Ym1pdEJ1dHRvblRhcmdldC5jbGFzc0xpc3QudG9nZ2xlKCdoaWRkZW4nLCB0aGlzLmN1cnJlbnRWYWx1ZSAhPT0gdGhpcy50b3RhbFZhbHVlKTtcblxuICAgICAgICAvLyBTY2hyaXR0d2VjaHNlbCBmw7xyIFNjcmVlbnJlYWRlciBhbnNhZ2VuIChBSy0yNCkg4oCTIG5pY2h0IGJlaW0gZXJzdGVuXG4gICAgICAgIC8vIFJlbmRlcm4gKGNvbm5lY3QpLCBudXIgd2VubiBkZXIgTnV0emVyIHdlY2hzZWx0LlxuICAgICAgICBpZiAoYW5ub3VuY2UpIHtcbiAgICAgICAgICAgIHRoaXMuYW5ub3VuY2VTdGVwKCk7XG4gICAgICAgIH1cbiAgICB9XG5cbiAgICAvKipcbiAgICAgKiBTYWd0IGRlbiBuZXVlbiBTY2hyaXR0IHNhbXQgUG9zaXRpb24gKFwiU2Nocml0dCAyIHZvbiA1OiDigKZcIikgaW4gZWluZXJcbiAgICAgKiBMaXZlLVJlZ2lvbiBhbi4gTXVzdGVyIHdpZSBvcmdhbmlzYXRpb25fdHlwZV9jb250cm9sbGVyOiBrdXJ6IGxlZXJlbixcbiAgICAgKiBkYW1pdCBhdWNoIGVpbiB3aWVkZXJob2x0IGdld8OkaGx0ZXIgU2Nocml0dCBlcm5ldXQgdm9yZ2VsZXNlbiB3aXJkLlxuICAgICAqL1xuICAgIHByaXZhdGUgYW5ub3VuY2VTdGVwKCk6IHZvaWQge1xuICAgICAgICBpZiAoIXRoaXMuaGFzQW5ub3VuY2VyVGFyZ2V0IHx8ICF0aGlzLmFubm91bmNlVGVtcGxhdGVWYWx1ZSkge1xuICAgICAgICAgICAgcmV0dXJuO1xuICAgICAgICB9XG5cbiAgICAgICAgY29uc3QgaW5kaWNhdG9yID0gdGhpcy5pbmRpY2F0b3JUYXJnZXRzW3RoaXMuY3VycmVudFZhbHVlIC0gMV07XG4gICAgICAgIGNvbnN0IHRpdGxlID0gaW5kaWNhdG9yPy5xdWVyeVNlbGVjdG9yPEhUTUxFbGVtZW50PignW2RhdGEtbGFiZWxdJyk/LnRleHRDb250ZW50Py50cmltKCkgPz8gJyc7XG5cbiAgICAgICAgY29uc3QgbWVzc2FnZSA9IHRoaXMuYW5ub3VuY2VUZW1wbGF0ZVZhbHVlXG4gICAgICAgICAgICAucmVwbGFjZSgnJWN1cnJlbnQlJywgU3RyaW5nKHRoaXMuY3VycmVudFZhbHVlKSlcbiAgICAgICAgICAgIC5yZXBsYWNlKCcldG90YWwlJywgU3RyaW5nKHRoaXMudG90YWxWYWx1ZSkpXG4gICAgICAgICAgICAucmVwbGFjZSgnJXRpdGxlJScsIHRpdGxlKTtcblxuICAgICAgICB0aGlzLmFubm91bmNlclRhcmdldC50ZXh0Q29udGVudCA9ICcnO1xuICAgICAgICB3aW5kb3cuc2V0VGltZW91dCgoKSA9PiB7XG4gICAgICAgICAgICB0aGlzLmFubm91bmNlclRhcmdldC50ZXh0Q29udGVudCA9IG1lc3NhZ2U7XG4gICAgICAgIH0sIDUwKTtcbiAgICB9XG59XG4iLCJpbXBvcnQgeyBDb250cm9sbGVyIH0gZnJvbSAnQGhvdHdpcmVkL3N0aW11bHVzJztcbmltcG9ydCBUb21TZWxlY3QgZnJvbSAndG9tLXNlbGVjdCc7XG5cbmV4cG9ydCBkZWZhdWx0IGNsYXNzIGV4dGVuZHMgQ29udHJvbGxlciB7XG4gICAgc3RhdGljIHZhbHVlcyA9IHtcbiAgICAgICAgdXJsOiBTdHJpbmcsXG4gICAgICAgIGNyZWF0ZVVybDogU3RyaW5nLFxuICAgIH07XG5cbiAgICBkZWNsYXJlIHVybFZhbHVlOiBzdHJpbmc7XG4gICAgZGVjbGFyZSBjcmVhdGVVcmxWYWx1ZTogc3RyaW5nO1xuXG4gICAgcHJpdmF0ZSB0b21TZWxlY3QhOiBUb21TZWxlY3Q7XG5cbiAgICBjb25uZWN0KCk6IHZvaWQge1xuICAgICAgICBjb25zdCBzZWxlY3RFbGVtZW50ID0gdGhpcy5lbGVtZW50IGFzIEhUTUxTZWxlY3RFbGVtZW50O1xuXG4gICAgICAgIHRoaXMudG9tU2VsZWN0ID0gbmV3IFRvbVNlbGVjdChzZWxlY3RFbGVtZW50LCB7XG4gICAgICAgICAgICBwbHVnaW5zOiBbJ3JlbW92ZV9idXR0b24nXSxcbiAgICAgICAgICAgIHZhbHVlRmllbGQ6ICdpZCcsXG4gICAgICAgICAgICBsYWJlbEZpZWxkOiAnbmFtZScsXG4gICAgICAgICAgICBzZWFyY2hGaWVsZDogWyduYW1lJ10sXG4gICAgICAgICAgICBjcmVhdGU6IHRoaXMuY3JlYXRlVXJsVmFsdWUgPyB0aGlzLmhhbmRsZUNyZWF0ZS5iaW5kKHRoaXMpIDogZmFsc2UsXG4gICAgICAgICAgICBsb2FkOiB0aGlzLnVybFZhbHVlID8gdGhpcy5oYW5kbGVMb2FkLmJpbmQodGhpcykgOiB1bmRlZmluZWQsXG4gICAgICAgICAgICByZW5kZXI6IHtcbiAgICAgICAgICAgICAgICBvcHRpb25fY3JlYXRlOiAoZGF0YTogeyBpbnB1dDogc3RyaW5nIH0pID0+IHtcbiAgICAgICAgICAgICAgICAgICAgcmV0dXJuIGA8ZGl2IGNsYXNzPVwiY3JlYXRlXCI+KyA8c3Ryb25nPiR7dGhpcy5lc2NhcGVIdG1sKGRhdGEuaW5wdXQpfTwvc3Ryb25nPiBoaW56dWbDvGdlbjwvZGl2PmA7XG4gICAgICAgICAgICAgICAgfSxcbiAgICAgICAgICAgIH0sXG4gICAgICAgIH0pO1xuXG4gICAgICAgIC8vIEFLLTQxOiBBdXN3YWhsIGbDvHIgU2NyZWVucmVhZGVyIGFuc2FnZW4uXG4gICAgICAgIC8vIERpZSBWb3JzY2hsw6RnZSBzZWxic3QgdHLDpGd0IFRvbSBTZWxlY3QgYmVyZWl0cyBiYXJyaWVyZWZyZWkgYXVzOlxuICAgICAgICAvLyByb2xlPVwiY29tYm9ib3hcIiwgYXJpYS1leHBhbmRlZCwgYXJpYS1jb250cm9scyBzb3dpZSBhcmlhLWFjdGl2ZWRlc2NlbmRhbnQvXG4gICAgICAgIC8vIGFyaWEtc2VsZWN0ZWQgYXVmIGRlbiBPcHRpb25lbiBpbSBMaXN0Ym94LURyb3Bkb3duLiBXYXMgZmVobHQsIGlzdCBkaWVcbiAgICAgICAgLy8gQW5zYWdlIGRlciBHRVRST0ZGRU5FTiBBdXN3YWhsLiBEYWbDvHIgd2lyZCBkaWUgQ2hpcC1MZWlzdGUgKC50cy1jb250cm9sKVxuICAgICAgICAvLyB6dSBlaW5lciBow7ZmbGljaGVuIExpdmUtUmVnaW9uOiBFaW4gbmV1IGhpbnp1Z2Vmw7xndGVyIEvDvGNoZW4tTmFtZSB3aXJkXG4gICAgICAgIC8vIHZvcmdlbGVzZW4uIERlciBDaGlwLVRleHQgdHLDpGd0IGRpZSBBdXNzYWdlIOKAkyBrZWluIG5ldWVyIMOcYmVyc2V0enVuZ3MtXG4gICAgICAgIC8vIHNjaGzDvHNzZWwgbsO2dGlnLiBXaXJkIG5hY2ggZGVyIEluaXRpYWxpc2llcnVuZyBnZXNldHp0LCBkYW1pdCBkaWUgYmVyZWl0c1xuICAgICAgICAvLyB2b3JoYW5kZW5lbiBDaGlwcyBiZWltIExhZGVuIG5pY2h0IHZvcmdlbGVzZW4gd2VyZGVuLlxuICAgICAgICB0aGlzLnRvbVNlbGVjdC5jb250cm9sLnNldEF0dHJpYnV0ZSgnYXJpYS1saXZlJywgJ3BvbGl0ZScpO1xuICAgICAgICB0aGlzLnRvbVNlbGVjdC5jb250cm9sLnNldEF0dHJpYnV0ZSgnYXJpYS1yZWxldmFudCcsICdhZGRpdGlvbnMnKTtcbiAgICB9XG5cbiAgICBkaXNjb25uZWN0KCk6IHZvaWQge1xuICAgICAgICB0aGlzLnRvbVNlbGVjdD8uZGVzdHJveSgpO1xuICAgIH1cblxuICAgIHByaXZhdGUgaGFuZGxlTG9hZChxdWVyeTogc3RyaW5nLCBjYWxsYmFjazogKHJlc3VsdHM6IEFycmF5PHsgaWQ6IHN0cmluZzsgbmFtZTogc3RyaW5nIH0+KSA9PiB2b2lkKTogdm9pZCB7XG4gICAgICAgIGNvbnN0IHVybCA9IGAke3RoaXMudXJsVmFsdWV9P3E9JHtlbmNvZGVVUklDb21wb25lbnQocXVlcnkpfWA7XG4gICAgICAgIGZldGNoKHVybClcbiAgICAgICAgICAgIC50aGVuKChyZXNwb25zZSkgPT4gcmVzcG9uc2UuanNvbigpKVxuICAgICAgICAgICAgLnRoZW4oKGRhdGE6IEFycmF5PHsgaWQ6IG51bWJlcjsgbmFtZTogc3RyaW5nIH0+KSA9PiB7XG4gICAgICAgICAgICAgICAgY2FsbGJhY2soZGF0YS5tYXAoKGl0ZW0pID0+ICh7XG4gICAgICAgICAgICAgICAgICAgIGlkOiBTdHJpbmcoaXRlbS5pZCksXG4gICAgICAgICAgICAgICAgICAgIG5hbWU6IGl0ZW0ubmFtZSxcbiAgICAgICAgICAgICAgICB9KSkpO1xuICAgICAgICAgICAgfSlcbiAgICAgICAgICAgIC5jYXRjaCgoKSA9PiBjYWxsYmFjayhbXSkpO1xuICAgIH1cblxuICAgIHByaXZhdGUgaGFuZGxlQ3JlYXRlKGlucHV0OiBzdHJpbmcsIGNhbGxiYWNrOiAoaXRlbT86IHsgaWQ6IHN0cmluZzsgbmFtZTogc3RyaW5nIH0pID0+IHZvaWQpOiBib29sZWFuIHtcbiAgICAgICAgZmV0Y2godGhpcy5jcmVhdGVVcmxWYWx1ZSwge1xuICAgICAgICAgICAgbWV0aG9kOiAnUE9TVCcsXG4gICAgICAgICAgICBoZWFkZXJzOiB7ICdDb250ZW50LVR5cGUnOiAnYXBwbGljYXRpb24vanNvbicgfSxcbiAgICAgICAgICAgIGJvZHk6IEpTT04uc3RyaW5naWZ5KHsgbmFtZTogaW5wdXQgfSksXG4gICAgICAgIH0pXG4gICAgICAgICAgICAudGhlbigocmVzcG9uc2UpID0+IHJlc3BvbnNlLmpzb24oKSlcbiAgICAgICAgICAgIC50aGVuKChkYXRhOiB7IGlkOiBudW1iZXI7IG5hbWU6IHN0cmluZyB9KSA9PiB7XG4gICAgICAgICAgICAgICAgY2FsbGJhY2soeyBpZDogU3RyaW5nKGRhdGEuaWQpLCBuYW1lOiBkYXRhLm5hbWUgfSk7XG4gICAgICAgICAgICB9KVxuICAgICAgICAgICAgLmNhdGNoKCgpID0+IGNhbGxiYWNrKCkpO1xuXG4gICAgICAgIHJldHVybiB0cnVlO1xuICAgIH1cblxuICAgIHByaXZhdGUgZXNjYXBlSHRtbCh0ZXh0OiBzdHJpbmcpOiBzdHJpbmcge1xuICAgICAgICBjb25zdCBkaXYgPSBkb2N1bWVudC5jcmVhdGVFbGVtZW50KCdkaXYnKTtcbiAgICAgICAgZGl2LnRleHRDb250ZW50ID0gdGV4dDtcbiAgICAgICAgcmV0dXJuIGRpdi5pbm5lckhUTUw7XG4gICAgfVxufVxuIiwiaW1wb3J0IHsgQ29udHJvbGxlciB9IGZyb20gJ0Bob3R3aXJlZC9zdGltdWx1cyc7XG5cbi8qKlxuICogTMO2c3QgZWluIEVyZWlnbmlzIGRlciBOdXR6dW5nc21lc3N1bmcgYXVzIChGZWF0dXJlIDExKS5cbiAqXG4gKiBEcmVpIEFydGVuLCBlcyB6dSB2ZXJ3ZW5kZW46XG4gKlxuICogLSAqKmJlaW0gRXJzY2hlaW5lbioqIOKAlCBgZGF0YS11c2FnZS1ldmVudC1vbi1jb25uZWN0LXZhbHVlPVwidHJ1ZVwiYDogc2VuZGV0LCBzb2JhbGQgZGFzIEVsZW1lbnRcbiAqICAgaW0gRG9rdW1lbnQgc3RlaHQuIEbDvHIgZGllIEVyZm9sZ3NtZWxkdW5nIGRlciBXYXJ0ZWxpc3RlbiwgZGllIG51ciBiZWkgRXJmb2xnIGdlcmVuZGVydCB3aXJkLlxuICogLSAqKmJlaW0gS2xpY2sgLyBiZWltIEFic2VuZGVuKiog4oCUIGBkYXRhLWFjdGlvbj1cImNsaWNrLT51c2FnZS1ldmVudCN0cmFja1wiYCBiencuIGBzdWJtaXQtPuKApmAuXG4gKiAtICoqRmlsdGVyZm9ybXVsYXIqKiDigJQgYGRhdGEtYWN0aW9uPVwic3VibWl0LT51c2FnZS1ldmVudCNmaWx0ZXJcImA6IHNhbW1lbHQgZGllIGdlc2V0enRlbiBGaWx0ZXIuXG4gKlxuICog4pqgICoqTmllIHdhcnRlbiwgbmllIGRpZSBOYXZpZ2F0aW9uIGFuaGFsdGVuKiogKEVudHd1cmYsIEVudHNjaGVpZHVuZyAzKS4gVW1hbWlzIGVpZ2VuZVxuICogS2xpY2stQXR0cmlidXRlIGhhbHRlbiBiZWkgTGlua3Mgb2huZSBgdGFyZ2V0PVwiX2JsYW5rXCJgIGRpZSBOYXZpZ2F0aW9uIGFuLCBiaXMgZGVyIFrDpGhsYXVmcnVmXG4gKiBmZXJ0aWcgaXN0IOKAlCBiZWkgYHRlbDpgIHVuZCBgbWFpbHRvOmAgd2FydGV0ZSBkZXIgQmVzdWNoZXIgYXVmIGRpZSBNZXNzdW5nLiBEZXNoYWxiIGhpZXIgb2huZVxuICogYGF3YWl0YDsgZGVyIFRyYWNrZXIgc2VuZGV0IG1pdCBga2VlcGFsaXZlYCwgZGVyIEF1ZnJ1ZiDDvGJlcmxlYnQgZGVuIFNlaXRlbndlY2hzZWwuXG4gKlxuICog4pqgICoqT2huZSBaw6RobHNrcmlwdCB0dXQgZGllc2VyIENvbnRyb2xsZXIgbmljaHRzIHVuZCB3aXJmdCBuaWNodHMqKiAoRUMtMDEsIEVDLTA2KToga2VpbiBTa3JpcHRcbiAqIGF1ZiBkZXIgU2VpdGUsIFdlcmJlYmxvY2tlciwgV2lkZXJzcHJ1Y2gsIG9mZmxpbmUuXG4gKlxuICog4pqgICoqTmllIFdlcnRlLCBkaWUgZWluZSBQZXJzb24gYmVzY2hyZWliZW4uKiogRGF0ZW4ga29tbWVuIGF1c3NjaGxpZcOfbGljaCBhdXNcbiAqIGBkYXRhLXVzYWdlLWV2ZW50LWRhdGEtdmFsdWVgLCBkYXMgZGllIFZvcmxhZ2UgZmVzdCBzZXR6dCDigJQgbmllIGF1cyBgaHJlZmAsIEZvcm11bGFyZmVsZGVybiBtaXRcbiAqIEZyZWl0ZXh0IG9kZXIgZGVtIFNlaXRlbnRleHQuIERpZSBXZWl0ZXJsZWl0dW5nIHdlaXN0IGFsbGVzIGFuZGVyZSBvaG5laGluIGFiLlxuICovXG5leHBvcnQgZGVmYXVsdCBjbGFzcyBleHRlbmRzIENvbnRyb2xsZXIge1xuICAgIHN0YXRpYyB2YWx1ZXMgPSB7XG4gICAgICAgIG5hbWU6IFN0cmluZyxcbiAgICAgICAgZGF0YTogeyB0eXBlOiBPYmplY3QsIGRlZmF1bHQ6IHt9IH0sXG4gICAgICAgIG9uQ29ubmVjdDogeyB0eXBlOiBCb29sZWFuLCBkZWZhdWx0OiBmYWxzZSB9LFxuICAgIH07XG5cbiAgICBkZWNsYXJlIHJlYWRvbmx5IG5hbWVWYWx1ZTogc3RyaW5nO1xuICAgIGRlY2xhcmUgcmVhZG9ubHkgZGF0YVZhbHVlOiBSZWNvcmQ8c3RyaW5nLCBzdHJpbmc+O1xuICAgIGRlY2xhcmUgcmVhZG9ubHkgb25Db25uZWN0VmFsdWU6IGJvb2xlYW47XG5cbiAgICBjb25uZWN0KCk6IHZvaWQge1xuICAgICAgICBpZiAodGhpcy5vbkNvbm5lY3RWYWx1ZSkge1xuICAgICAgICAgICAgdGhpcy4jc2VuZCh0aGlzLmRhdGFWYWx1ZSk7XG4gICAgICAgIH1cbiAgICB9XG5cbiAgICB0cmFjaygpOiB2b2lkIHtcbiAgICAgICAgdGhpcy4jc2VuZCh0aGlzLmRhdGFWYWx1ZSk7XG4gICAgfVxuXG4gICAgLyoqXG4gICAgICogRmlsdGVyZm9ybXVsYXIgZGVyIFJlc3RhdXJhbnRsaXN0ZSAoQUstMTMpLlxuICAgICAqXG4gICAgICogw5xiZXJ0cmFnZW4gd2VyZGVuIG51ciBkaWUgKipOYW1lbioqIGFuZ2VoYWt0ZXIgSmEvTmVpbi1GZWxkZXIuIERlciBPcnQgKGBjaXR5YCkgdW5kIGRpZSBLw7xjaGVuXG4gICAgICogKGBjdWlzaW5lW11gKSBnZWhlbiBudXIgYWxzIE1lcmtlciBgb3J0YCBiencuIGBrdWVjaGVgIGhpbmF1cyDigJQgbmllIGlociBXZXJ0LlxuICAgICAqL1xuICAgIGZpbHRlcigpOiB2b2lkIHtcbiAgICAgICAgY29uc3QgZm9ybXVsYXIgPSB0aGlzLmVsZW1lbnQgYXMgSFRNTEZvcm1FbGVtZW50O1xuICAgICAgICBjb25zdCBlaW50cmFlZ2U6IHN0cmluZ1tdID0gW107XG5cbiAgICAgICAgZm9ybXVsYXIucXVlcnlTZWxlY3RvckFsbDxIVE1MSW5wdXRFbGVtZW50PignaW5wdXRbdHlwZT1cImNoZWNrYm94XCJdW3ZhbHVlPVwiMVwiXScpLmZvckVhY2goKGZlbGQpID0+IHtcbiAgICAgICAgICAgIGlmIChmZWxkLmNoZWNrZWQgJiYgL15bYS16X10rJC8udGVzdChmZWxkLm5hbWUpKSB7XG4gICAgICAgICAgICAgICAgZWludHJhZWdlLnB1c2goZmVsZC5uYW1lKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfSk7XG5cbiAgICAgICAgY29uc3Qgb3J0ID0gZm9ybXVsYXIucXVlcnlTZWxlY3RvcjxIVE1MSW5wdXRFbGVtZW50PignaW5wdXRbbmFtZT1cImNpdHlcIl0nKTtcbiAgICAgICAgaWYgKG9ydCAmJiBvcnQudmFsdWUudHJpbSgpICE9PSAnJykge1xuICAgICAgICAgICAgZWludHJhZWdlLnB1c2goJ29ydCcpO1xuICAgICAgICB9XG5cbiAgICAgICAgY29uc3Qga3VlY2hlbiA9IGZvcm11bGFyLnF1ZXJ5U2VsZWN0b3I8SFRNTFNlbGVjdEVsZW1lbnQ+KCdzZWxlY3RbbmFtZT1cImN1aXNpbmVbXVwiXScpO1xuICAgICAgICBpZiAoa3VlY2hlbiAmJiBrdWVjaGVuLnNlbGVjdGVkT3B0aW9ucy5sZW5ndGggPiAwKSB7XG4gICAgICAgICAgICBlaW50cmFlZ2UucHVzaCgna3VlY2hlJyk7XG4gICAgICAgIH1cblxuICAgICAgICBpZiAoZWludHJhZWdlLmxlbmd0aCA9PT0gMCkge1xuICAgICAgICAgICAgcmV0dXJuO1xuICAgICAgICB9XG5cbiAgICAgICAgdGhpcy4jc2VuZCh7IGZpbHRlcjogWy4uLm5ldyBTZXQoZWludHJhZWdlKV0uam9pbignLCcpIH0pO1xuICAgIH1cblxuICAgICNzZW5kKGRhdGVuOiBSZWNvcmQ8c3RyaW5nLCBzdHJpbmc+KTogdm9pZCB7XG4gICAgICAgIHRyeSB7XG4gICAgICAgICAgICBjb25zdCB1bWFtaSA9IHdpbmRvdy51bWFtaTtcbiAgICAgICAgICAgIGlmICghdW1hbWkgfHwgdHlwZW9mIHVtYW1pLnRyYWNrICE9PSAnZnVuY3Rpb24nIHx8IHRoaXMubmFtZVZhbHVlID09PSAnJykge1xuICAgICAgICAgICAgICAgIHJldHVybjtcbiAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgY29uc3QgZXJnZWJuaXMgPSB1bWFtaS50cmFjayh0aGlzLm5hbWVWYWx1ZSwgT2JqZWN0LmtleXMoZGF0ZW4pLmxlbmd0aCA+IDAgPyBkYXRlbiA6IHVuZGVmaW5lZCk7XG4gICAgICAgICAgICB2b2lkIFByb21pc2UucmVzb2x2ZShlcmdlYm5pcykuY2F0Y2goKCkgPT4ge1xuICAgICAgICAgICAgICAgIC8vIERpZSBNZXNzdW5nIGRhcmYgbmllIGVpbmVuIEZlaGxlciBpbiBkaWUgU2VpdGUgdHJhZ2VuLlxuICAgICAgICAgICAgfSk7XG4gICAgICAgIH0gY2F0Y2gge1xuICAgICAgICAgICAgLy8gU2llaGUgb2Jlbi5cbiAgICAgICAgfVxuICAgIH1cbn1cbiIsImltcG9ydCB7IENvbnRyb2xsZXIgfSBmcm9tICdAaG90d2lyZWQvc3RpbXVsdXMnO1xuXG4vKiogRGVyc2VsYmUgU2NobMO8c3NlbCwgZGVuIGRlciBVbWFtaS1UcmFja2VyIHZvciBqZWRlbSBWZXJzYW5kIGxpZXN0LiAqL1xuY29uc3QgU0NITFVFU1NFTCA9ICd1bWFtaS5kaXNhYmxlZCc7XG5cbi8qKlxuICogV2lkZXJzcHJ1Y2hzc2NoYWx0ZXIgZGVyIE51dHp1bmdzbWVzc3VuZyBpbiAvbGVnYWwgKEZlYXR1cmUgMTEsIEFLLTIzKS5cbiAqXG4gKiDimqAgKipLZWluIENvb2tpZSoqIOKAlCBkZXIgQmFubmVydGV4dCDigJ5XaXIgbnV0emVuIG51ciB0ZWNobmlzY2ggbm90d2VuZGlnZSBDb29raWVzXCIgbXVzcyB3YWhyXG4gKiBibGVpYmVuIChBSy0yMCkuIERlciBTY2hhbHRlciBzY2hyZWlidCBgdW1hbWkuZGlzYWJsZWRgIGluIGRlbiBCcm93c2Vyc3BlaWNoZXIuIEdlbmF1IGRpZXNlblxuICogU2NobMO8c3NlbCBwcsO8ZnQgZGVyIFVtYW1pLVRyYWNrZXIgdm9yICoqamVkZW0qKiBWZXJzYW5kIHNlbGJzdCAoRW50d3VyZiwgRW50c2NoZWlkdW5nIDEwKSDigJQgZWluXG4gKiBlaWdlbmVyIE1lcmtlciB3w6RyZSBlaW5lIHp3ZWl0ZSBTdGVsbGUsIGRpZSBhdXNlaW5hbmRlcmxhdWZlbiBrYW5uLlxuICpcbiAqIOKaoCBEZXIgS25vcGYgaXN0IGltIE1hcmt1cCBgaGlkZGVuYCB1bmQgd2lyZCBlcnN0IGhpZXIgc2ljaHRiYXI6IE9obmUgSmF2YVNjcmlwdCB3aXJkIG9obmVoaW4gbmljaHRcbiAqIGdlbWVzc2VuIChFQy0wMiksIHVuZCBlaW4gS25vcGYsIGRlciBuaWNodHMgdHV0LCB3w6RyZSBzY2hsZWNodGVyIGFscyBrZWluZXIuIElzdCBkZXJcbiAqIEJyb3dzZXJzcGVpY2hlciBnZXNwZXJydCwgZXJzY2hlaW50IHN0YXR0ZGVzc2VuIGRlciBIaW53ZWlzIOKAnm5pY2h0IHZlcmbDvGdiYXJcIi5cbiAqXG4gKiBHZWxlZXJ0ZXIgQnJvd3NlcnNwZWljaGVyIGhlYnQgZGVuIFdpZGVyc3BydWNoIGF1ZiAoRUMtMDcpIOKAlCBvaG5lIENvb2tpZSB1bmQgb2huZSBLb250byBrYW5uIGRlclxuICogU2NoYWx0ZXIgc2ljaCBuaWNodHMgZGF1ZXJoYWZ0ZXIgbWVya2VuLiBgL2xlZ2FsYCBzYWd0IGRhcy5cbiAqL1xuZXhwb3J0IGRlZmF1bHQgY2xhc3MgZXh0ZW5kcyBDb250cm9sbGVyIHtcbiAgICBzdGF0aWMgdGFyZ2V0cyA9IFsnYnV0dG9uJywgJ3N0YXRlJywgJ3VuYXZhaWxhYmxlJ107XG4gICAgc3RhdGljIHZhbHVlcyA9IHtcbiAgICAgICAgb25UZXh0OiBTdHJpbmcsXG4gICAgICAgIG9mZlRleHQ6IFN0cmluZyxcbiAgICB9O1xuXG4gICAgZGVjbGFyZSByZWFkb25seSBidXR0b25UYXJnZXQ6IEhUTUxCdXR0b25FbGVtZW50O1xuICAgIGRlY2xhcmUgcmVhZG9ubHkgc3RhdGVUYXJnZXQ6IEhUTUxFbGVtZW50O1xuICAgIGRlY2xhcmUgcmVhZG9ubHkgdW5hdmFpbGFibGVUYXJnZXQ6IEhUTUxFbGVtZW50O1xuICAgIGRlY2xhcmUgcmVhZG9ubHkgb25UZXh0VmFsdWU6IHN0cmluZztcbiAgICBkZWNsYXJlIHJlYWRvbmx5IG9mZlRleHRWYWx1ZTogc3RyaW5nO1xuXG4gICAgY29ubmVjdCgpOiB2b2lkIHtcbiAgICAgICAgY29uc3Qgc3BlaWNoZXIgPSB0aGlzLiNzcGVpY2hlcigpO1xuICAgICAgICBpZiAoc3BlaWNoZXIgPT09IG51bGwpIHtcbiAgICAgICAgICAgIHRoaXMudW5hdmFpbGFibGVUYXJnZXQuaGlkZGVuID0gZmFsc2U7XG5cbiAgICAgICAgICAgIHJldHVybjtcbiAgICAgICAgfVxuXG4gICAgICAgIHRoaXMuYnV0dG9uVGFyZ2V0LmhpZGRlbiA9IGZhbHNlO1xuICAgICAgICB0aGlzLiN6ZWlnZShzcGVpY2hlcik7XG4gICAgfVxuXG4gICAgdG9nZ2xlKCk6IHZvaWQge1xuICAgICAgICBjb25zdCBzcGVpY2hlciA9IHRoaXMuI3NwZWljaGVyKCk7XG4gICAgICAgIGlmIChzcGVpY2hlciA9PT0gbnVsbCkge1xuICAgICAgICAgICAgcmV0dXJuO1xuICAgICAgICB9XG5cbiAgICAgICAgaWYgKHRoaXMuI2lzdEF1cyhzcGVpY2hlcikpIHtcbiAgICAgICAgICAgIHNwZWljaGVyLnJlbW92ZUl0ZW0oU0NITFVFU1NFTCk7XG4gICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICBzcGVpY2hlci5zZXRJdGVtKFNDSExVRVNTRUwsICcxJyk7XG4gICAgICAgIH1cblxuICAgICAgICB0aGlzLiN6ZWlnZShzcGVpY2hlcik7XG4gICAgfVxuXG4gICAgI3plaWdlKHNwZWljaGVyOiBTdG9yYWdlKTogdm9pZCB7XG4gICAgICAgIGNvbnN0IGF1cyA9IHRoaXMuI2lzdEF1cyhzcGVpY2hlcik7XG4gICAgICAgIC8vIGFyaWEtcHJlc3NlZD1cInRydWVcIiBoZWnDn3Q6IERpZSBNZXNzdW5nIGlzdCBBTi4gRGVyIFp1c3RhbmQgc3RlaHQgenVzw6R0emxpY2ggYWxzIFdvcnQgZGEg4oCUXG4gICAgICAgIC8vIEZhcmJlIHRyw6RndCBuaWUgYWxsZWluLlxuICAgICAgICB0aGlzLmJ1dHRvblRhcmdldC5zZXRBdHRyaWJ1dGUoJ2FyaWEtcHJlc3NlZCcsIGF1cyA/ICdmYWxzZScgOiAndHJ1ZScpO1xuICAgICAgICB0aGlzLnN0YXRlVGFyZ2V0LnRleHRDb250ZW50ID0gYXVzID8gdGhpcy5vZmZUZXh0VmFsdWUgOiB0aGlzLm9uVGV4dFZhbHVlO1xuICAgIH1cblxuICAgICNpc3RBdXMoc3BlaWNoZXI6IFN0b3JhZ2UpOiBib29sZWFuIHtcbiAgICAgICAgcmV0dXJuIHNwZWljaGVyLmdldEl0ZW0oU0NITFVFU1NFTCkgIT09IG51bGw7XG4gICAgfVxuXG4gICAgI3NwZWljaGVyKCk6IFN0b3JhZ2UgfCBudWxsIHtcbiAgICAgICAgdHJ5IHtcbiAgICAgICAgICAgIGNvbnN0IHNwZWljaGVyID0gd2luZG93LmxvY2FsU3RvcmFnZTtcbiAgICAgICAgICAgIGNvbnN0IHByb2JlID0gJ2VuZGxlY2guc3BlaWNoZXJwcm9iZSc7XG4gICAgICAgICAgICBzcGVpY2hlci5zZXRJdGVtKHByb2JlLCAnMScpO1xuICAgICAgICAgICAgc3BlaWNoZXIucmVtb3ZlSXRlbShwcm9iZSk7XG5cbiAgICAgICAgICAgIHJldHVybiBzcGVpY2hlcjtcbiAgICAgICAgfSBjYXRjaCB7XG4gICAgICAgICAgICByZXR1cm4gbnVsbDtcbiAgICAgICAgfVxuICAgIH1cbn1cbiIsIi8vIHNyYy90dXJib19jb250cm9sbGVyLnRzXG5pbXBvcnQgeyBDb250cm9sbGVyIH0gZnJvbSBcIkBob3R3aXJlZC9zdGltdWx1c1wiO1xuaW1wb3J0IFwiQGhvdHdpcmVkL3R1cmJvXCI7XG52YXIgdHVyYm9fY29udHJvbGxlcl9kZWZhdWx0ID0gY2xhc3MgZXh0ZW5kcyBDb250cm9sbGVyIHtcbn07XG5leHBvcnQge1xuICB0dXJib19jb250cm9sbGVyX2RlZmF1bHQgYXMgZGVmYXVsdFxufTtcbiJdLCJuYW1lcyI6WyJHTGlnaHRib3giLCJkb2N1bWVudCIsImFkZEV2ZW50TGlzdGVuZXIiLCJzZWxlY3RvciIsIm5hdmlnYXRvciIsIndpbmRvdyIsInNlcnZpY2VXb3JrZXIiLCJyZWdpc3RlciIsInNjb3BlIiwic3RhcnRTdGltdWx1c0FwcCIsIkF1dGhlbnRpY2F0aW9uQ29udHJvbGxlciIsIlJlZ2lzdHJhdGlvbkNvbnRyb2xsZXIiLCJhcHAiLCJyZXF1aXJlIiwiY29udGV4dCIsIkFVU0dFTk9NTUVORV9QRkFERSIsIlRPS0VOIiwidm9yVmVyc2FuZCIsIl90eXAiLCJudXR6bGFzdCIsImdsb2JhbFByaXZhY3lDb250cm9sIiwicGZhZCIsInBmYWRBdXMiLCJ1cmwiLCJ0ZXN0IiwiYWRyZXNzZSIsIlVSTCIsImxvY2F0aW9uIiwiaHJlZiIsInBhdGhuYW1lIiwiX3VudXNlZCIsImVuZGxlY2hOdXR6dW5nVm9yVmVyc2FuZCIsIkNvbnRyb2xsZXIiLCJkZWZhdWx0XzEiLCJfQ29udHJvbGxlciIsIl90aGlzIiwiX2NsYXNzQ2FsbENoZWNrIiwiX2RlZmF1bHRfMV9pbmRleCIsInNldCIsIl9pbmhlcml0cyIsIl9jcmVhdGVDbGFzcyIsImtleSIsInZhbHVlIiwiY29ubmVjdCIsIl9fY2xhc3NQcml2YXRlRmllbGRTZXQiLCJlbnRyeVRhcmdldHMiLCJsZW5ndGgiLCJhZGRFbnRyeSIsImh0bWwiLCJwcm90b3R5cGVWYWx1ZSIsInJlcGxhY2UiLCJTdHJpbmciLCJfX2NsYXNzUHJpdmF0ZUZpZWxkR2V0IiwiX2EiLCJ3cmFwcGVyIiwiY3JlYXRlRWxlbWVudCIsImNsYXNzTGlzdCIsImFkZCIsInNldEF0dHJpYnV0ZSIsImlubmVySFRNTCIsImVudHJpZXNUYXJnZXQiLCJhcHBlbmRDaGlsZCIsInJlbW92ZUVudHJ5IiwiZXZlbnQiLCJ0YXJnZXQiLCJlbnRyeSIsImNsb3Nlc3QiLCJyZW1vdmUiLCJ0YXJnZXRzIiwidmFsdWVzIiwicHJvdG90eXBlIiwiaGFzQmFubmVyVGFyZ2V0IiwiX2RlZmF1bHRfMV9pbnN0YW5jZXMiLCJfZGVmYXVsdF8xX2hhc0NvbnNlbnQiLCJjYWxsIiwiX2RlZmF1bHRfMV9zaG93IiwiYWNjZXB0IiwiX2RlZmF1bHRfMV9zZXRDb25zZW50IiwiX2RlZmF1bHRfMV9oaWRlIiwiZGVjbGluZSIsIm9wZW5TZXR0aW5ncyIsImRpc3BhdGNoIiwicmVvcGVuIiwiYmFubmVyVGFyZ2V0IiwiZm9jdXMiLCJfZGVmYXVsdF8xX3JlYWRDb29raWUiLCJjb29raWVOYW1lVmFsdWUiLCJtYXhBZ2UiLCJsaWZldGltZVZhbHVlIiwiY29va2llIiwiY29uY2F0IiwicHJvdG9jb2wiLCJuYW1lIiwiZXNjYXBlZCIsIm1hdGNoIiwiUmVnRXhwIiwiZGVjb2RlVVJJQ29tcG9uZW50IiwiY29va2llTmFtZSIsInR5cGUiLCJsaWZldGltZSIsIk51bWJlciIsIl9kZWZhdWx0IiwiX2NhbGxTdXBlciIsImFyZ3VtZW50cyIsImVsZW1lbnQiLCJ0ZXh0Q29udGVudCIsImRlZmF1bHQiLCJTb3J0YWJsZSIsIl90aGlzMiIsImNyZWF0ZSIsImxpc3RUYXJnZXQiLCJoYW5kbGUiLCJnaG9zdENsYXNzIiwiYW5pbWF0aW9uIiwib25FbmQiLCJfZGVmYXVsdF8xX3VwZGF0ZUJ1dHRvbnMiLCJfZGVmYXVsdF8xX3BlcnNpc3QiLCJtb3ZlVXAiLCJidXR0b24iLCJjdXJyZW50VGFyZ2V0Iiwicm93IiwicHJldmlvdXMiLCJwcmV2aW91c0VsZW1lbnRTaWJsaW5nIiwiYmVmb3JlIiwiX2RlZmF1bHRfMV9hZnRlck1vdmUiLCJtb3ZlRG93biIsIm5leHQiLCJuZXh0RWxlbWVudFNpYmxpbmciLCJhZnRlciIsImRpc2FibGVkIiwiZmFsbGJhY2siLCJxdWVyeVNlbGVjdG9yIiwicm93cyIsIkFycmF5IiwiZnJvbSIsInF1ZXJ5U2VsZWN0b3JBbGwiLCJmb3JFYWNoIiwiaW5kZXgiLCJ1cCIsImRvd24iLCJfZGVmYXVsdF8xX3BlcnNpc3QyIiwiX2FzeW5jVG9HZW5lcmF0b3IiLCJfcmVnZW5lcmF0b3IiLCJtIiwiX2NhbGxlZSIsIml0ZW1zIiwiaW1hZ2VJZHMiLCJ3IiwiX2NvbnRleHQiLCJuIiwibWFwIiwiZWwiLCJkYXRhc2V0IiwiaW1hZ2VJZCIsImJhZGdlIiwic3R5bGUiLCJkaXNwbGF5IiwiZmV0Y2giLCJ1cmxWYWx1ZSIsIm1ldGhvZCIsImhlYWRlcnMiLCJib2R5IiwiSlNPTiIsInN0cmluZ2lmeSIsIl90b2tlbiIsInRva2VuVmFsdWUiLCJhIiwiYXBwbHkiLCJ0b2tlbiIsInRvZ2dsZSIsInN0b3BQcm9wYWdhdGlvbiIsImlzT3BlbiIsIm1lbnVUYXJnZXQiLCJjb250YWlucyIsImNsb3NlTWVudSIsIm9wZW5NZW51IiwiY2xvc2UiLCJjbG9zZU9uRXNjYXBlIiwiYnV0dG9uVGFyZ2V0IiwiYXJyb3dUYXJnZXQiLCJvbk91dHNpZGVDbGljayIsIm9wZW4iLCJvbktleWRvd24iLCJfdGhpcyRlbGVtZW50JHF1ZXJ5U2UiLCJkaXNjb25uZWN0IiwicmVtb3ZlRXZlbnRMaXN0ZW5lciIsImFkZFNsb3QiLCJkYXkiLCJvcGVuaW5nSG91cnNGb3JtRGF5UGFyYW0iLCJjb250YWluZXIiLCJkYXlJbnB1dCIsInJlbW92ZVNsb3QiLCJzbG90IiwidXBkYXRlIiwiY2hhbmdlIiwiYW5ub3VuY2UiLCJzZWxlY3RlZCIsInNlbGVjdGVkVHlwZSIsImJsb2NrVGFyZ2V0cyIsImJsb2NrIiwibWF0Y2hlcyIsImhpZGRlbiIsImZpZWxkIiwiY2hlY2tlZCIsIl9ibG9jayRkYXRhc2V0JGxhYmVsIiwiaGFzQW5ub3VuY2VyVGFyZ2V0IiwiZmluZCIsImIiLCJsYWJlbCIsImFubm91bmNlclRhcmdldCIsInNldFRpbWVvdXQiLCJhbm5vdW5jZW1lbnRWYWx1ZSIsImFubm91bmNlbWVudCIsImlkbGVMYWJlbCIsImhhc1BhbmVsVGFyZ2V0IiwiX2RlZmF1bHRfMV9icm93c2VyU3VwcG9ydHNQYXNza2V5cyIsInBhbmVsVGFyZ2V0IiwiaGFzQnV0dG9uVGFyZ2V0IiwiX3RoaXMkYnV0dG9uVGFyZ2V0JHRlIiwic3RhcnQiLCJfZGVmYXVsdF8xX2NsZWFyTWVzc2FnZSIsImJ1c3lWYWx1ZSIsInVuc3VwcG9ydGVkIiwiX2RlZmF1bHRfMV9yZXNldCIsIl9kZWZhdWx0XzFfc2hvd01lc3NhZ2UiLCJ1bnN1cHBvcnRlZFZhbHVlIiwiY2VyZW1vbnlFcnJvciIsIl9ldmVudCRkZXRhaWwiLCJjb2RlIiwiZGV0YWlsIiwiZXhpc3RzVmFsdWUiLCJjb25maWdWYWx1ZSIsImZhaWxlZFZhbHVlIiwic2VydmVyRXJyb3IiLCJzZXJ2ZXJWYWx1ZSIsIlB1YmxpY0tleUNyZWRlbnRpYWwiLCJyZW1vdmVBdHRyaWJ1dGUiLCJ0ZXh0IiwiaGFzTWVzc2FnZVRhcmdldCIsIm1lc3NhZ2VUYXJnZXQiLCJmYWlsZWQiLCJzZXJ2ZXIiLCJleGlzdHMiLCJjb25maWciLCJidXN5IiwiTUlTU0lOR19DTEFTU0VTIiwidXBkYXRlVmlldyIsInZhbGlkYXRlU3RlcCIsImN1cnJlbnRWYWx1ZSIsInRvdGFsVmFsdWUiLCJwcmV2IiwiZ29UbyIsInN0ZXAiLCJwYXJzZUludCIsIl9taXNzaW5nJHF1ZXJ5U2VsZWN0byIsInN0ZXBUYXJnZXRzIiwiZ3JvdXBzIiwiaXNBbnN3ZXJlZCIsImdyb3VwIiwiX2kiLCJfZ3JvdXBzIiwiX2dyb3VwJGNsYXNzTGlzdCIsImFuc3dlcmVkIiwibWlzc2luZyIsImNsZWFyRXJyb3JzIiwiaGFzRXJyb3JUYXJnZXQiLCJlcnJvclRhcmdldCIsImluY29tcGxldGVNZXNzYWdlVmFsdWUiLCJzY3JvbGxJbnRvVmlldyIsImJlaGF2aW9yIiwicHJldmVudFNjcm9sbCIsIl9ncm91cCRjbGFzc0xpc3QyIiwidW5kZWZpbmVkIiwiaW5kaWNhdG9yVGFyZ2V0cyIsInN0ZXBOdW0iLCJjaXJjbGUiLCJsaW5lIiwicHJldkJ1dHRvblRhcmdldCIsIm5leHRCdXR0b25UYXJnZXQiLCJzdWJtaXRCdXR0b25UYXJnZXQiLCJhbm5vdW5jZVN0ZXAiLCJfaW5kaWNhdG9yJHF1ZXJ5U2VsZWMiLCJfaW5kaWNhdG9yJHF1ZXJ5U2VsZWMyIiwiYW5ub3VuY2VUZW1wbGF0ZVZhbHVlIiwiaW5kaWNhdG9yIiwidGl0bGUiLCJ0cmltIiwibWVzc2FnZSIsImN1cnJlbnQiLCJ0b3RhbCIsImluY29tcGxldGVNZXNzYWdlIiwiYW5ub3VuY2VUZW1wbGF0ZSIsIlRvbVNlbGVjdCIsInNlbGVjdEVsZW1lbnQiLCJ0b21TZWxlY3QiLCJwbHVnaW5zIiwidmFsdWVGaWVsZCIsImxhYmVsRmllbGQiLCJzZWFyY2hGaWVsZCIsImNyZWF0ZVVybFZhbHVlIiwiaGFuZGxlQ3JlYXRlIiwiYmluZCIsImxvYWQiLCJoYW5kbGVMb2FkIiwicmVuZGVyIiwib3B0aW9uX2NyZWF0ZSIsImRhdGEiLCJlc2NhcGVIdG1sIiwiaW5wdXQiLCJjb250cm9sIiwiX3RoaXMkdG9tU2VsZWN0IiwiZGVzdHJveSIsInF1ZXJ5IiwiY2FsbGJhY2siLCJlbmNvZGVVUklDb21wb25lbnQiLCJ0aGVuIiwicmVzcG9uc2UiLCJqc29uIiwiaXRlbSIsImlkIiwiZGl2IiwiY3JlYXRlVXJsIiwib25Db25uZWN0VmFsdWUiLCJfZGVmYXVsdF8xX3NlbmQiLCJkYXRhVmFsdWUiLCJ0cmFjayIsImZpbHRlciIsImZvcm11bGFyIiwiZWludHJhZWdlIiwiZmVsZCIsInB1c2giLCJvcnQiLCJrdWVjaGVuIiwic2VsZWN0ZWRPcHRpb25zIiwiX3RvQ29uc3VtYWJsZUFycmF5IiwiU2V0Iiwiam9pbiIsImRhdGVuIiwidW1hbWkiLCJuYW1lVmFsdWUiLCJlcmdlYm5pcyIsIk9iamVjdCIsImtleXMiLCJQcm9taXNlIiwicmVzb2x2ZSIsIm9uQ29ubmVjdCIsIkJvb2xlYW4iLCJTQ0hMVUVTU0VMIiwic3BlaWNoZXIiLCJfZGVmYXVsdF8xX3NwZWljaGVyIiwidW5hdmFpbGFibGVUYXJnZXQiLCJfZGVmYXVsdF8xX3plaWdlIiwiX2RlZmF1bHRfMV9pc3RBdXMiLCJyZW1vdmVJdGVtIiwic2V0SXRlbSIsImF1cyIsInN0YXRlVGFyZ2V0Iiwib2ZmVGV4dFZhbHVlIiwib25UZXh0VmFsdWUiLCJnZXRJdGVtIiwibG9jYWxTdG9yYWdlIiwicHJvYmUiLCJvblRleHQiLCJvZmZUZXh0IiwidHVyYm9fY29udHJvbGxlcl9kZWZhdWx0Il0sImlnbm9yZUxpc3QiOltdLCJzb3VyY2VSb290IjoiIn0=