import {appointment,nextStatus} from './domain.mjs';
const key='nordea-demo-v1';
const user={identifiant_ad:'demo-conseiller',nom:'Bernard',prenom:'Alex',role:'conseiller_particuliers'};
function seed(){return {user,can:{clients_write:true,appointments_write:true,requests_read:true,requests_write:true},advisors:[user],clients:[{id_client:1,type_client:'particulier',nom_ou_raison_sociale:'Martin',prenom:'Camille',email:'camille.martin@example.test',telephone:null,conseiller_ad:user.identifiant_ad},{id_client:2,type_client:'particulier',nom_ou_raison_sociale:'Durand',prenom:'Lou',email:null,telephone:null,conseiller_ad:user.identifiant_ad}],appointments:[{id_rdv:1,id_client:1,conseiller_ad:user.identifiant_ad,debut:'2026-11-09T09:00',fin:'2026-11-09T09:30',objet:'Échange sur un projet personnel',statut:'prevu'}],requests:[{id_demande:1,id_client:1,objet:'Attestation administrative',description:'Demande fictive de document administratif.',date_creation:'2026-11-05T08:30',statut:'nouvelle'}],history:[{id_historique:1,id_demande:1,statut:'nouvelle',commentaire:'Demande créée',date_changement:'2026-11-05T08:30',auteur_ad:user.identifiant_ad}]};}
function get(){try{const data=JSON.parse(localStorage.getItem(key));if(data?.user?.identifiant_ad===user.identifiant_ad&&Array.isArray(data.clients))return data;}catch{}return seed();}
function save(data){localStorage.setItem(key,JSON.stringify(data));}
export async function demoRequest(route,method='GET',body={}){const data=get(),[resource,id,action]=route.split('/');
 if(route==='session'||route==='login')return {user,csrf:'demo'};
 if(route==='logout')return {};
 if(route==='bootstrap')return data;
 const nextId=rows=>Math.max(0,...rows.map(r=>Number(Object.values(r)[0])||0))+1;
 if(resource==='clients'&&['POST','PATCH'].includes(method)){
  if(!['particulier','professionnel'].includes(body.type_client)||!body.nom_ou_raison_sociale?.trim()||(body.type_client==='particulier'&&!body.prenom?.trim()))throw Error('Complétez le nom et le prénom du particulier.');
  if(body.email&&!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(body.email))throw Error('Le courriel n’est pas valide.');
  const item={id_client:id?Number(id):nextId(data.clients),...body,conseiller_ad:user.identifiant_ad};const i=data.clients.findIndex(c=>c.id_client===Number(id));if(id&&i<0)throw Error('Client introuvable.');if(i>=0)data.clients[i]=item;else data.clients.push(item);save(data);return item;
 }
 if(resource==='appointments'&&method==='POST'){
  if(!data.clients.some(c=>c.id_client===Number(body.id_client)))throw Error('Client introuvable.');
  const item={id_rdv:nextId(data.appointments),...appointment(body,data.appointments),statut:'prevu'};data.appointments.push(item);save(data);return item;
 }
 if(resource==='appointments'&&method==='PATCH'){
  const i=data.appointments.findIndex(r=>r.id_rdv===Number(id));if(i<0)throw Error('Rendez-vous introuvable.');if(data.appointments[i].statut!=='prevu')throw Error('Ce rendez-vous ne peut plus être modifié.');
  data.appointments[i]=body.statut==='annule'?{...data.appointments[i],statut:'annule'}:{...data.appointments[i],...appointment(body,data.appointments,id)};save(data);return data.appointments[i];
 }
 if(resource==='requests'&&method==='POST'&&!id){if(!body.objet?.trim()||!data.clients.some(c=>c.id_client===Number(body.id_client)))throw Error('Client et objet obligatoires.');const item={id_demande:nextId(data.requests),...body,date_creation:new Date().toISOString().slice(0,16),statut:'nouvelle'};data.requests.push(item);data.history.push({id_historique:nextId(data.history),id_demande:item.id_demande,statut:'nouvelle',commentaire:null,date_changement:item.date_creation,auteur_ad:user.identifiant_ad});save(data);return item;}
 if(resource==='requests'&&method==='POST'&&action==='transitions'){const item=data.requests.find(d=>d.id_demande===Number(id));if(!item)throw Error('Demande introuvable.');item.statut=nextStatus(item.statut,body.statut);data.history.push({id_historique:nextId(data.history),id_demande:item.id_demande,statut:item.statut,commentaire:body.commentaire||null,date_changement:new Date().toISOString().slice(0,16),auteur_ad:user.identifiant_ad});save(data);return item;}
 throw Error('Action indisponible dans la démonstration.');
}
