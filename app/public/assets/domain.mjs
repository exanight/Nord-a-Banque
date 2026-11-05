export const labels={prevu:'Prévu',termine:'Terminé',annule:'Annulé',nouvelle:'Nouvelle',en_cours:'En cours',cloturee:'Clôturée',particulier:'Particulier',professionnel:'Professionnel',direction:'Direction',accueil:'Accueil',conseiller_particuliers:'Conseiller particuliers',conseiller_professionnels:'Conseiller professionnels',administration:'Administration',conformite:'Risques et conformité'};
export function appointment(input,existing=[],exclude=null){
 const match=/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})$/.exec(input.debut||'');
 if(!match)throw Error('Choisissez une date et une heure valides.');
 const [,y,m,d,h,min]=match.map(Number),date=new Date(Date.UTC(y,m-1,d,h,min));
 if(date.getUTCFullYear()!==y||date.getUTCMonth()!==m-1||date.getUTCDate()!==d||h<9||h>16||![0,30].includes(min)||[0,6].includes(date.getUTCDay()))throw Error('Créneaux de 30 minutes, du lundi au vendredi, entre 9 h et 17 h.');
 if(!String(input.objet||'').trim()||input.objet.length>150)throw Error('Précisez un objet de 1 à 150 caractères.');
 if(!input.id_client||!input.conseiller_ad)throw Error('Sélectionnez un client et un conseiller.');
 if(existing.some(r=>String(r.id_rdv)!==String(exclude)&&r.statut!=='annule'&&r.conseiller_ad===input.conseiller_ad&&r.debut.replace(' ','T').slice(0,16)===input.debut))throw Error('Ce créneau est déjà réservé pour ce conseiller. Choisissez-en un autre.');
 date.setUTCMinutes(date.getUTCMinutes()+30);
 return {...input,objet:input.objet.trim(),fin:date.toISOString().slice(0,16)};
}
export function nextStatus(current,next){if(!((current==='nouvelle'&&next==='en_cours')||(current==='en_cours'&&next==='cloturee')))throw Error('Ce changement de statut n’est pas autorisé.');return next;}
export function escapeHtml(value){return String(value??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));}
