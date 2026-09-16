# Dokumentation

Die Dokumente hier beschreiben zusammen, was Endlech.lu ist, wie es gebaut ist
und wie es aussieht.

| Dokument | Inhalt | Für wen |
|---|---|---|
| **[prd.md](prd.md)** | Vision, Zielgruppen, Produktprinzipien, Funktionsumfang, Kennzahlen, Geschäftsmodell, Roadmap, Risiken | Alle, die verstehen wollen, worum es geht — auch ohne Code zu lesen |
| **[data-model.md](data-model.md)** | Alle Entities, Enums, Repositories und Migrationen mit Feldern, Typen, Constraints und Relationen | Wer am Backend arbeitet |
| **[design-system.md](design-system.md)** | Farben, Typografie, Komponenten, Barrierefreiheits-Regeln, Diagramme, Druckansicht | Wer an Templates oder Assets arbeitet |
| **[app-shell.md](app-shell.md)** | Layout-Hierarchie, Navigation, Kopf- und Fußzeile, Admin-Shell, bekannte Lücken | Wer am Rahmen arbeitet, der auf jeder Seite gleich ist |
| **[architektur/gesamtarchitektur.html](architektur/gesamtarchitektur.html)** | Interaktives Diagramm: Browser/iOS-App, Coolify-Proxy, Anwendung, Worker und MariaDB bei Hostinger, zweiter VPS (Umami, Uptime Kuma), Brevo, Sentry EU. Stand 2026-09-15 | Wer verstehen will, was wo läuft und wer mit wem spricht |

**Abgrenzung zu den übrigen Dokumenten im Repo:**

- [`../README.md`](../README.md) — Installation, Betrieb, Deployment
- [`../CHANGELOG.md`](../CHANGELOG.md) — was wann veröffentlicht wurde (CalVer)
- [`../features/index.md`](../features/index.md) — Feature-Inventar der SDD-Kette (26 Bestandsfeatures)
- [`../CLAUDE.md`](../CLAUDE.md) — Arbeitsanweisung für KI-Assistenten, mit den
  Implementierungs-Fallstricken, die man beim Ändern kennen muss

Bei Widersprüchen gilt der Code.

**Das Architekturdiagramm neu erzeugen:** Quelle ist `architektur/gesamtarchitektur.architecture.json`; das HTML
entsteht mit dem Skill `archify` (`node bin/archify.mjs deliver architecture <json> <html> --quality showcase`).
Wer einen Dienst ergänzt oder entfernt, ändert die JSON-Datei und erzeugt das HTML neu — nie das HTML von Hand.
