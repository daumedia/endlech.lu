# Schemata für die Sitemap-Prüfung (Feature 10, AK-01)

| Datei | Herkunft |
|---|---|
| `sitemap.xsd` | **unverändert** von <https://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd>, abgerufen am 2026-09-12. © Google Inc., Yahoo! Inc. und Microsoft Corporation, veröffentlicht unter [Creative Commons Attribution-ShareAlike](https://creativecommons.org/licenses/by-sa/2.5/) |
| `sitemap-mit-sprachverweisen.xsd` | eigenes Schema für `xhtml:link`, das `sitemap.xsd` einbindet |

⚠ **Warum es zwei Dateien braucht.** `sitemap.xsd` lässt fremde Elemente im `<url>` nur mit
`processContents="strict"` zu: Der Prüfer muss für jedes fremde Element eine Deklaration
kennen. Die Sprachverweise (`xhtml:link`) stehen in keinem Schema, das sitemaps.org mitliefert
— gegen `sitemap.xsd` allein fällt deshalb **jede korrekte Sitemap mit Sprachverweisen**
durch. Geprüft wird gegen `sitemap-mit-sprachverweisen.xsd`, das beides kennt.

`sitemap.xsd` bleibt unverändert, damit ein späterer Abgleich mit dem Original ein `diff`
ist und keine Suche.
