# Events2Lrs Plugin

[TOC]

## About

Das Events2Lrs-Plugin sendet Daten an einen LRS (Learning Record Store). Ausgangspunkte können Daten aus (optional erweiterten) ILIAS-Events und Daten aus der Nutzung von (auch über den Seiten-Editor) eingebundenen H5P-Objekten sein.
Zu den Events, auf die das Plugin reagiert, zählen beispielsweise Statusaktualisierungen von ILIAS-Objekten mit Lernfortschritt, wie z.B. Kurse und darin enthaltene Lernmodule.

Es wird empfohlen, die vom Plugin erfassten Daten pseudonymisiert an den LRS zu übersenden. Die erforderlichen Einstellungen finden Sie unter 'Administration' -> 'xAPI/cmi5' -> 'LRS-Typen'.
Eine Rückreferenzierung von den im LRS gespeicherten Daten zum Anwender ist dann ausschließlich durch die ILIAS-Installation möglich. Hierzu dient das Plugin `LrsDashboard`.

Sofern das Plugin nicht in den ILIAS-Nutzungsbestimmungen berücksichtigt wird, wird empfohlen, ergänzend das Plugin `Lp2LrsPrivacy` ab Version 1.2 zu installieren. Hiermit können Kurs-Nutzende selbst entscheiden, ob Daten über das Events2Lrs-Plugin an einen LRS gesendet werden. 

## Features

Folgende ILIAS-Objekte können u. A. erfasst werden:

- Kurs
- Test
- Lernmodul

Darüber hinaus können z. B. ILIAS-Lernmodule auch externe H5P-Libraries enthalten, für die ebenfalls Benutzer-Interaktionen erfasst werden können. 

Eine Klassifizierung erfasster Daten erfolgt beispielsweise nach

- Interacted

- Answered

- Completed

Folgende Informationen können in xAPI-Statements (Datensätze im JSON-Format) enthalten sein:

- Information über das ILIAS-Objekt (ID, Typ, Pfad, Titel, Keywords)
- Speicher-Datum & -Zeit, eindeutige ID  des Statements im LRS
- Benutzerdaten (Pseudonymisierung empfohlen), Ergebnisse und Lernerfolgstatus

# Systemanforderungen

- PHP 7.3
- MySQL 5.7
- ILIAS 7.x, 6.x
- aktivierter ILIAS Soap-Server
- LRS (empfohlen: LearningLocker)

# Installation

Zur Installation können Sie folgende Befehle auf der Kommandozeile ausführen:

```
mkdir -p /[ILIAS_DOC_ROOT]/Customizing/global/plugins/Services/Cron/CronHook
cd /[ILIAS_DOC_ROOT]/Customizing/global/plugins/Services/Cron/CronHook
git clone https://github.com/internetlehrer/Events2Lrs.git Events2Lrs
```

Melden Sie sich anschließend mit Ihrem Webbrowser als Systemadministrator bei Ihrer ILIAS-Installation an. Navigieren Sie dann zur Plugin-Administration unter dem Pfad:

 `Administration > ILIAS erweitern > Plugins` 

In der Plugin-Übersicht finden Sie den Eintrag Events2Lrs. Klicken Sie in der Zeile auf das Dropdown-Feld `Aktionen`. Im Dropdown-Menü klicken Sie dann auf `Installieren`, anschließend auf `Konfigurieren` zur Auswahl u.a. des LRS und abließend erneut im Dropdown-Menü auf `Aktivieren`.


## Patches

Damit das Plugin alle vorgesehnen Events erfassen kann, müssen einige ILIAS Core-Dateien unter Anwendung von Patches modifiziert werden. Die anzuwendenden Patch-Dateien sowie alternativ bereits modifizierte Klassen-Dateien, liegen dem Plugin bei. Die Verzeichnisstruktur in denen die Patch-Dateien zu finden sind, entspricht der Verzeichnisstruktur der in ILIAS zu modifizierenden Dateien. 

Wenn Sie PhpStorm verwenden, navigieren Sie in das Verzeichnis mit dem gewünschten Patch und öffnen Sie mit einem Rechtsklick auf die Patch-Datei das Kontextmenü. Im Kontextmenü klicken Sie auf den Menüeintrag `Apply Patch`. Alternativ können Sie die, in den Verzeichnissen enthaltenen `.php` und `.xml` Dateien in das Zielverzeichnis kopieren. 

Auf der Kommandozeile (Git-Bash), können Sie nachfolgende Befehle ausführen. Ersetzen Sie dabei {release_n} mit der Release-Nummer Ihrer ILIAS-Installation:

`cd /your ILIAS Installation/`

### Services/Tracking

`git apply Customizing/global/plugins/Services/Cron/CronHook/Events2Lrs/patches/release_n/Services/Tracking/service.xml.patch`

`git apply Customizing/global/plugins/Services/Cron/CronHook/Events2Lrs/patches/release_n/Services/Tracking/classes/class.ilChangeEvent.php.patch`

### Modules/LearningModule

`git apply Customizing/global/plugins/Services/Cron/CronHook/Events2Lrs/patches/release_n/Modules/LearningModule/module.xml.patch`

`git apply Customizing/global/plugins/Services/Cron/CronHook/Events2Lrs/patches/release_n/Modules/LearningModule/classes/class.ilLMTracker.php.patch`

### Modules/Test

`git apply Customizing/global/plugins/Services/Cron/CronHook/Events2Lrs/patches/release_n/Modules/Test/module.xml.patch`

`git apply Customizing/global/plugins/Services/Cron/CronHook/Events2Lrs/patches/release_n/Modules/Test/classes/class.ilTestOutputGUI.php.patch`

`git apply Customizing/global/plugins/Services/Cron/CronHook/Events2Lrs/patches/release_n/Modules/Test/classes/class.ilTestPlayerAbstractGUI.php.patch`

If git apply is ending with an error maybe this helps:
`git apply --ignore-space-change Customizing/...`

# Event Handling

Das Plugin Events2Lrs reagiert auf bestimmte Events, die von ILIAS-Komponenten zu bestimmten Anlässen geworfen werden, indem aus den Event-Parametern xAPI-Statements generiert und diese an den in den Plugin-Einstellungen ausgewählten LRS (Learning Record Store) gesendet werden. 

## Übersicht unterstützter Events

Zum aktuellen Entwicklungsstand unterstützt das Events2Lrs-Plugin folgende Events:

### Zugriffe, Zeiten, Lernfortschritt

#### afterChangeEvent

Über dieses Event wird die geänderte Zeit in Containerobjekten infolge der Nutzung von in den Containern liegenden Objekten erfasst. Voraussetzung für die Erfassung der Zeiten ist, dass der über 'Administration' -> 'Lernerfolge' -> 'Zugriffsstatistiken und Lernfortschritt' einstellbare Wert für 'Maximale Zeit zwischen Anfragen' hoch genug ist, um den Aufruf von Objekten nicht als neuen Aufruf zu werten. Ansonsten bleibt die Zeit unverändert und das Statement kennzeichnet lediglich einen neuen Aufruf eines im Containerobjekt liegenden Objekts. Unabhängig vom Wert für 'Maximale Zeit zwischen Anfragen' bedeutet ein Statement, dass ein Aufruf eines im Container liegenden Objekts erfolgt ist. Wir empfehlen also den Wert für 'Maximale Zeit zwischen Anfragen' in Abhängigkeit der zu erfassenden Objekte deutlich zu erhöhen - z.B. auf 3600 Sekunden. Dies gilt mit einer Einschränkung: Sollte der Lernfortschrittsmodus bei ILIAS-Lernmodulen eingestellt sein auf 'Status wird anhand der Anzahl der Aufrufe bestimmt', so sollte der Wert für 'Maximale Zeit zwischen Anfragen' deutlich reduziert werden. Die per Statement an den LRS übertragenen Werte für Zeiten entsprechen hier der Spalte 'childs_spent_seconds' in der Datenbank-Tabelle 'read_event'.

#### readCounterChange

Über dieses Event werden Aufrufe von ILIAS-Objekten erfasst. Zur Bedeutung des Werts für 'Maximale Zeit zwischen Anfragen' gelten die Aussagen wie beim afterChangeEvent. Hohe Werte dienen der besseren Erfassung von Bearbeitungszeiten. Niedrige Werte erhöhen die Werte für 'read_count' in der Datenbank-Tabelle 'read_event'. Die per Statement an den LRS übertragenen Werte für Zeiten entsprechen hier der Spalte 'spent_seconds'. Bitte beachten Sie, dass die Werte aus 'read_count' auch über 'Administration' -> 'Magazin und Objekte' -> 'Magazin' auf Info-Seiten angezeigt werden können durch Aktivierung von 'Anzahl Zugriffe anzeigen'. Wir empfehlen, die Anzahl der Zugriffe nicht anzuzeigen (ILIAS-Voreinstellung) und den Wert für 'Maximale Zeit zwischen Anfragen' zu erhöhen.

#### trackIliasLearningModulePageAccess

Dieses Event erfasst den Seitenwechsel in ILIAS-Lernmodulen

#### updateStatus

Dieses Event erfasst Daten zu Lerfortschrittsänderungen

### H5P (former uiEvent)

### Test

For a more detailed description, refer to the [documentation](docs/README.md).

#### startTestPass

Dieses Event erfasst das Starten eines Tests

#### resumeTestPass

Dieses Event erfasst das Überschreiten der Bearbeitungsdauer eines Tests

#### suspendTestPass

Dieses Event erfasst das Unterbrechen eines Tests

#### finishTestPass

Dieses Event erfasst das Beenden eines Tests

#### finishTestResponse

Dieses Event erfasst die bei Testaufgaben von Absolvierenden gemachten Angaben

### Plugin-Workflow

#### sendAllStatements

Der auf stündliche Ausführung voreingestellte Event2Lrs-CronJob wirft das Event 'sendAllStatements' nur dann, wenn zuvor Statements über BackgroundTasks nicht erfolgreich gesendet werden konnten. Die im Fehlerfall gespeicherten Basisdaten werden ausgelesen, Statements daraus generiert und diese an den LRS gesendet. Die Datenbankeinträge zu erfolgreich übersendeten Statements werden dann gelöscht. Andernfalls wird maximal 5 mal versucht, die Statements zu versenden. Nicht erfolgreich versandte Statements werden in der Plugin-Konfiguration unter 'Statements' angezeigt und können dort manuell gelöscht werden. 

## Weitere Events hinzufügen

Damit der Plugin-EventHandler auf weitere Events reagiert, müssen Pluginseits folgende Voraussetzungen erfüllt sein:

- xml event-tag mit listen-property der angesprochenen Component

- in der Plugin-Datei plugin.ini.json im Knoten eventTask muss der Bezeichner des Events sowie der auszuführende Task notiert sein. Beispiel: `"afterChangeEvent": "SendSingleStatement"`

- im Plugin-Ordner `classes/Statement` muss eine Klasse vorhanden sein, die zum Erzeugen des xAPI-Statements mit dem in der .ini-Datei angelegten Event-Bezeichner aufgerufen werden kann, wie zum Beispiel `class AfterChangeEvent extends XapiStatement`
- im Plugin-Ordner `classes/Event` muss eine Klasse vorhanden sein, die vom Plugin-EventHandler mit dem in der .ini-Datei angelegten Event-Bezeichner aufgerufen werden kann, wie zum Beispiel `class AfterChangeEvent extends EventHandler`
- im Plugin-Ordner `classes/Task` muss eine Klasse vorhanden sein, die vom erweiternden EventHandler mit dem in der .ini-Datei notierten Task-Bezeichner instanziert werden kann, wie zum Beispiel `class SendSingleStatement extends AbstractJob`

Das Auslösen des Events kann an der gewünschten Stelle in ILIAS, wie z. B. in einem Modul, Service oder Plugin, durch Anwenden folgender Methode erfolgen:

```
/** @var \ILIAS\DI\Container $DIC */
$DIC->event()->raise(string $a_component, string $a_event, array $a_parameter);
```

wobei die Parameter wie folgt zu belegen sind:

- `$a_component` verlangt einen der in der plugin.xml notierten Event-Listener

- `$a_event` mit dem Event-Bezeichner, der in der Datei plugin.ini.json enthalten sein muss

- `$a_parameter` ein Array mit folgenden erforderlichen Angaben: 
      `obj_id`  die Objekt-ID des Event-auslösenden Objekts
      `ref_id`  die Ref-ID des Event-auslösenden Objekts
      `usr_id`  die Benutzer-ID des ILIAS-Users

  Weitere optionale Parameter können mit dem Array an den Plugin-EventHandler übergeben werden, wie zum Beispiel beim `afterChangeEvent` enthält der optionale Parameter mit dem Key `changeProp` ein weiteres Array mit Key/Value-Pairs, z. B. `'spent_seconds' => $diff` Die übergebenen optionalen Parameter können dann beispielsweise in der Statement-Klasse zum Erzeugen des `Result` Node wie folgt genutzt werden:

```
namespace ILIAS\Plugin\Events2Lrs\Statement;

class AfterChangeEvent extends XapiStatement
{
	public function buildResult(): ?array
	{
		return [
			"duration" => "PT" . $this->eventParam['changeProp']['spent_seconds'] . "S"
		];
	}
```



