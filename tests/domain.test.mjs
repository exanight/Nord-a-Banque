import {test} from 'node:test';
import assert from 'node:assert/strict';
import {appointment,nextStatus,escapeHtml} from '../app/public/assets/domain.mjs';
const base={id_client:1,conseiller_ad:'a',debut:'2026-11-09T09:00',objet:'Entretien'};
test('rendez-vous de trente minutes et dernier creneau',()=>{assert.equal(appointment(base).fin,'2026-11-09T09:30');assert.equal(appointment({...base,debut:'2026-11-09T16:30'}).fin,'2026-11-09T17:00');});
test('dates impossibles, week-end et horaires refusés',()=>{for(const debut of ['2026-11-07T09:00','2026-02-30T09:00','2026-11-09T08:30','2026-11-09T17:00','2026-11-09T09:15','2026-11-09T99:00'])assert.throws(()=>appointment({...base,debut}));});
test('doublon refuse, annulation libere, conseillers distincts acceptes',()=>{const row={...base,id_rdv:1,statut:'prevu'};assert.throws(()=>appointment(base,[row]));assert.doesNotThrow(()=>appointment(base,[{...row,statut:'annule'}]));assert.doesNotThrow(()=>appointment({...base,conseiller_ad:'b'},[row]));assert.doesNotThrow(()=>appointment(base,[row],1));});
test('transitions seulement dans le sens prevu',()=>{assert.equal(nextStatus('nouvelle','en_cours'),'en_cours');assert.equal(nextStatus('en_cours','cloturee'),'cloturee');assert.throws(()=>nextStatus('nouvelle','cloturee'));assert.throws(()=>nextStatus('cloturee','en_cours'));});
test('echappement des donnees inserees dans le HTML',()=>{assert.equal(escapeHtml('<img src=x onerror="alert(1)">'), '&lt;img src=x onerror=&quot;alert(1)&quot;&gt;');});
