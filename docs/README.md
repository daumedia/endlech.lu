# Dokumentation

Drei Dokumente, die zusammen beschreiben, was Endlech.lu ist, wie es gebaut ist
und wie es aussieht.

| Dokument | Inhalt | Für wen |
|---|---|---|
| **[prd.md](prd.md)** | Vision, Zielgruppen, Produktprinzipien, Funktionsumfang, Kennzahlen, Geschäftsmodell, Roadmap, Risiken | Alle, die verstehen wollen, worum es geht — auch ohne Code zu lesen |
| **[data-model.md](data-model.md)** | Alle Entities, Enums, Repositories und Migrationen mit Feldern, Typen, Constraints und Relationen | Wer am Backend arbeitet |
| **[design-system.md](design-system.md)** | Farben, Typografie, Komponenten, Barrierefreiheits-Regeln, Diagramme, Druckansicht | Wer an Templates oder Assets arbeitet |
| **[app-shell.md](app-shell.md)** | Layout-Hierarchie, Navigation, Kopf- und Fußzeile, Admin-Shell, bekannte Lücken | Wer am Rahmen arbeitet, der auf jeder Seite gleich ist |

**Abgrenzung zu den übrigen Dokumenten im Repo:**

- [`../README.md`](../README.md) — Installation, Betrieb, Deployment
- [`../CHANGELOG.md`](../CHANGELOG.md) — was wann veröffentlicht wurde (CalVer)
- [`../features/index.md`](../features/index.md) — Feature-Inventar der SDD-Kette (26 Bestandsfeatures)
- [`../CLAUDE.md`](../CLAUDE.md) — Arbeitsanweisung für KI-Assistenten, mit den
  Implementierungs-Fallstricken, die man beim Ändern kennen muss

Bei Widersprüchen gilt der Code.
