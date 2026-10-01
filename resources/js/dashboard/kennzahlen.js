import { formatDauer, formatEine, formatGanz, formatKompakt, formatProzent } from './format.js';

/** Die fünf Kennzahlen. `gut`: 1 = steigend ist gut, −1 = sinkend ist gut. */
export const KENNZAHLEN = [
  {
    id: 'besucher',
    label: 'Besucher',
    erklaerung: 'Anzahl unterschiedlicher Besucher pro Tag. Über mehrere Tage ist es die Summe der Tageswerte.',
    gut: 1,
    anzeige: formatKompakt,
    exakt: (n) => `${formatGanz(n)} Besucher`,
    achse: (n) => formatKompakt(n, 1000),
  },
  {
    id: 'aufrufe',
    label: 'Seitenaufrufe',
    erklaerung: 'Anzahl aller aufgerufenen Seiten, auch wiederholte Aufrufe derselben Seite.',
    gut: 1,
    anzeige: formatKompakt,
    exakt: (n) => `${formatGanz(n)} Seitenaufrufe`,
    achse: (n) => formatKompakt(n, 1000),
  },
  {
    id: 'seitenProBesuch',
    label: 'Seiten pro Besuch',
    erklaerung: 'Seitenaufrufe geteilt durch Besuche.',
    gut: 1,
    anzeige: formatEine,
    exakt: (n) => `${formatEine(n)} Seiten pro Besuch`,
    achse: formatEine,
  },
  {
    id: 'absprungrate',
    label: 'Absprungrate',
    erklaerung: 'Anteil der Besuche, die nach nur einer Seite endeten.',
    gut: -1,
    anzeige: formatProzent,
    exakt: (n) => `${formatProzent(n)} Absprungrate`,
    achse: (n) => `${formatGanz(n)} %`,
  },
  {
    id: 'dauer',
    label: 'Besuchsdauer',
    erklaerung: 'Durchschnittliche Dauer eines Besuchs, vom ersten bis zum letzten Aufruf.',
    gut: 1,
    anzeige: formatDauer,
    exakt: (n) => `${formatDauer(n)} Minuten Besuchsdauer`,
    achse: formatDauer,
  },
];

export const kennzahl = (id) => KENNZAHLEN.find((k) => k.id === id) ?? KENNZAHLEN[0];
