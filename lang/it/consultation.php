<?php

// This file is part of Consultation module for Moodle.
//
// Consultation is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Consultation is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Consultation.  If not, see <http://www.gnu.org/licenses/>.

/**
 * mod_consultation lang pack file.
 *
 * @package    mod
 * @subpackage consultation
 * @copyright  2009 Andrea Bicciolo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['addmynewpost'] = 'Invia';
$string['attachment'] = 'Allegato';
$string['bynameondate'] = 'per {$a->name} - {$a->date}';
$string['candidatesnotyet'] = 'Non ancora contattati';
$string['candidatesopened'] = 'Contattati in precedenza';
$string['cannotdeleteinquiry'] = 'Non è possibile eliminare richieste esistenti.';
$string['resolveinquiry'] = 'Chiudi la richiesta indicandola risolta';
$string['resolvedconsultations'] = 'Richieste chiuse';
$string['confirmclosure'] = 'Vuoi chiudere la richiesta <strong>{$a}</strong>?<br />Una volta chiusa la richiesta non sarà possibile aggingervi altri messaggi.';
$string['confirmdeleteinquiry'] = 'Sei certo di voler eliminare la richiesta <strong>{$a}</strong>?';
$string['confirmdeletepost'] = 'Sei certo di voler eliminare il seguente messaggio?<br /><br />{$a}';
$string['confirmreopen'] = 'Vuoi riaprire la seguente richiesta?<br /><br />{$a}';
$string['consultation:deleteany'] = 'Eliminare consultazioni';
$string['consultation:interrupt'] = 'Inserire richieste';
$string['consultation:answer'] = 'Ascoltare richieste';
$string['consultation:open'] = 'Aprire richieste di ascoltatori';
$string['consultation:openany'] = 'Rivolgere richieste a qualsiasi partecipante';
$string['consultation:reopen'] = 'Riaprire richieste';
$string['consultation:reopenany'] = 'Riaprire qualsiasi richiesta';
$string['consultation:resolve'] = 'Chiudere le proprie richieste';
$string['consultation:resolveany'] = 'Chiudere qualsiasi richiesta';
$string['consultation:viewany'] = 'Vedere qualsiasi richiesta';
$string['inquiryalreadyresolved'] = 'Spiacente, questa richiesta è già stata chiusa.';
$string['inquiryfromuser'] = 'Da';
$string['inquirylast'] = 'Modificata';
$string['inquiries'] = 'Richieste';
$string['inquiriestart'] = 'Inserita';
$string['inquiriesunreadcount'] = 'Messaggi non letti';
$string['inquirytouser'] = 'A';
$string['inquirywithuser'] = 'Utente';
$string['fromme'] = 'Da me';
$string['fullsubjectothers'] = 'Richieste di {$a->fromname} verso {$a->toname}: {$a->subject}';
$string['fullsubjectfromme'] = 'Mie richieste {$a->subject} ({$a->fullname})';
$string['fullsubjecttome'] = 'Risposte alla richiesta {$a->subject} ({$a->fullname})';
$string['consultationwith'] = 'Rivolgi richiesta a';
$string['interrupt'] = 'Interrompi';
$string['mailnewmessage'] = 'Notifica automatica dal sito {$a->site}, Modulo consultazione {$a->consultation}:
L\'utente {$a->from} ha inserito la richiesta {$a->inquiry} {$a->url}.';
$string['mailnewsubject'] = '{$a->course}: Nuova richiesta di consulto "{$a->inquiry}"';
$string['mailpostmessage'] = 'Notifica automatica dal sito {$a->site}, Modulo consultazione {$a->consultation}:
L\'utente {$a->from} ha aggiornato la richiesta {$a->inquiry} {$a->url}.';
$string['mailpostsubject'] = '{$a->course}: consultation inquiry "{$a->inquiry}"';
$string['message'] = 'Messaggio';
$string['moddeleteafter'] = 'Elimina richieste chiuse dopo';
$string['moddeleteafterexplain'] = 'Elimina automaticamente le richieste chouse una volta trascorso il tempo impostato.';
$string['modeditdefaults'] = 'Valori di default per le impostazioni dell\'attività';
$string['modeditdefaultsexplain'] = 'I valori qui impostati saranno i valori di default usati quando si crea una nuova attività di questo tipo. E\' anche possibile stabilire quali impostazioni sono da ritenere Impostazioni avanzate.';
$string['modedittime'] = 'Tempo max. di modifica richieste (minuti)';
$string['modedittimeexplain'] = 'Il tempo in minuti entro il quale i partecipanti sono autorizzati a modificare le richieste.';
$string['modintro'] = 'Introduzione';
$string['modname'] = 'Nome';
$string['modnotify'] = 'Avverti i partecipanti';
$string['modnotifyexplain'] = 'Avverte i partecipanti in caso di nuove richieste o di aggiornamenti di richeiste già effettuate.';
$string['modopenlimit'] = 'Numero max. richieste consentite';
$string['modopenlimitexplain'] = 'Il numero massimo di richieste consentite per ogni partecipante.';
$string['modulename'] = 'Consultazione';
$string['modulenameplural'] = 'Consultazioni';
$string['noavailablepeople'] = 'Spiacente ma non ci sono partecipanti';
$string['noguests'] = 'Agli ospiti non è consentito di effetture richieste.';
$string['noinquiries'] = 'Al momento non sono pervenute richieste.';
$string['numstartedinquiries'] = '{$a} richieste';
$string['openconsultation'] = 'Inserisci richiesta';
$string['openconsultations'] = 'Consultazioni aperte';
$string['refresh'] = 'Aggiorna';
$string['reopeninquiry'] = 'Riapri';
$string['resetconsultationsall'] = 'Elimina tutte le richieste';
$string['subject'] = 'Argomento';
$string['subtabresolvedmy'] = 'Richieste chiuse ({$a})';
$string['subtabresolvedothers'] = 'Di altri ({$a})';
$string['subtabviewmy'] = 'Mie ({$a})';
$string['subtabviewothers'] = 'Di altri ({$a})';
$string['tabopen'] = 'Inserisci richiesta';
$string['tabview'] = 'Richieste aperte';
$string['tabviewany'] = 'Richieste attive ({$a})';
$string['tabresolved'] = 'Richieste chiuse';
$string['tabresolvedany'] = 'Tutte le richieste chiuse ({$a})';
$string['tabunread'] = 'Da leggere ({$a})';
$string['timeeditoever'] = 'Non è possibile aggiornare ulteriormente il messaggio, il messaggio non è stato agigornato.';
$string['toomanyinquiries'] = 'Non potete aprire più di {$a} richieste.';
$string['untilwarning'] = '(Fino {$a})';
$string['updatedinquiries'] = 'Consultazioni aggiornate';
