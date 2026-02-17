import { router } from "@/plugins/1.router";
import { useArchiveStoreStore } from "@/stores/archive";
import { useAuthStore } from "@/stores/auth";
import { useUserStore } from "@/stores/user";

// 👉 Store call
const authStore = useAuthStore();
const store = useUserStore();
const archiveStore = useArchiveStoreStore();

export default [
  {
    title: 'Utilisateurs',
    to: { name: 'users',},
    icon: { icon: 'tabler-user' },
    meta: { isArchived: false },
    onClick: () => archiveStore.setArchiveMode(false),
  },
  {
    title: 'Clients',
    to: { name: 'clients' },
    icon: { icon: 'tabler-users-group' },
    onClick: () => archiveStore.setArchiveMode(false),

  },
  {
    title: 'Caisses',
    to: { name: 'cashregisters' },
    icon: { icon: 'tabler-premium-rights' },
    onClick: () => archiveStore.setArchiveMode(false),

  },
  {
    title: 'Documents',
    icon: { icon: 'tabler-file-invoice' },
    children: [
      { title: 'Demande de devis', to: 'documents-quoterequests', onClick: () => archiveStore.setArchiveMode(false) },
      { title: 'Devis', to: 'documents-quotes', onClick: () => archiveStore.setArchiveMode(false) },
      { title: 'Bons de commande', to: 'documents-ordernotes', onClick: () => archiveStore.setArchiveMode(false) },
      { title: 'Bons de production', to: 'documents-productionnotes', onClick: () => archiveStore.setArchiveMode(false) },
      { title: 'Bons de sortie', to: 'documents-outputnotes', onClick: () => archiveStore.setArchiveMode(false) },
      { title: 'Bons de livraison', to: 'documents-deliverynotes', onClick: () => archiveStore.setArchiveMode(false) },
      { title: 'Bons de retour', to: 'documents-returnnotes', onClick: () => archiveStore.setArchiveMode(false) },
      { title: 'Factures', to: 'documents-invoices', onClick: () => archiveStore.setArchiveMode(false) },
      { title: 'Reçus de commande', to: 'documents-orderreceipt', onClick: () => archiveStore.setArchiveMode(false) },
      { title: 'Factures avoir', to: 'documents-invoicecredits', onClick: () => archiveStore.setArchiveMode(false) },
      { title: 'Bons de remboursement', to: 'documents-refunds', onClick: () => archiveStore.setArchiveMode(false) },
   ],
  },
  {
    title: 'Paiements',
    to: { name: 'payments' },
    icon: { icon: 'tabler-brand-cashapp' },
    onClick: () => archiveStore.setArchiveMode(false),
  },
  {
    title: 'Emails',
    icon: { icon: 'tabler-mail' },
    to: 'email',
  },
  {
    title: 'Archives',
    icon: { icon: 'tabler-archive' },
    // onClick: () => {
    //   archiveStore.setArchiveMode(true)
    // },
    children: [
      { title: 'Utilisateurs', to: 'archives-users', onClick: () => archiveStore.setArchiveMode(true) },
      { title: 'Clients', to: 'archives-clients', onClick: () => archiveStore.setArchiveMode(true) },
      { title: 'Paiements', to: 'archives-payments', onClick: () => archiveStore.setArchiveMode(true) },
      { title: 'Caisses', to:'archives-cashregisters', onClick: () => archiveStore.setArchiveMode(true) },
      { title: 'Demande de devis', to: 'archives-documents-quoterequests', onClick: () => archiveStore.setArchiveMode(true) },
      { title: 'Devis', to: 'archives-documents-quotes', onClick: () => archiveStore.setArchiveMode(true) },
      { title: 'Bons de commande', to: 'archives-documents-ordernotes', onClick: () => archiveStore.setArchiveMode(true) },
      { title: 'Bons de production', to: 'archives-documents-productionnotes', onClick: () => archiveStore.setArchiveMode(true) },
      { title: 'Bons de sortie', to: 'archives-documents-outputnotes', onClick: () => archiveStore.setArchiveMode(true) },
      { title: 'Bons de livraison', to: 'archives-documents-deliverynotes', onClick: () => archiveStore.setArchiveMode(true) },
      { title: 'Bons de retour', to: 'archives-documents-returnnotes', onClick: () => archiveStore.setArchiveMode(true) },
      { title: 'Factures', to: 'archives-documents-invoices', onClick: () => archiveStore.setArchiveMode(true) },
      { title: 'Reçu de commande', to: 'archives-documents-orderreceipts', onClick: () => archiveStore.setArchiveMode(true) },
      { title: 'Factures avoir', to: 'archives-documents-invoicecredits', onClick: () => archiveStore.setArchiveMode(true) },
      { title: 'Bons de remboursement', to: 'archives-documents-refunds', onClick: () => archiveStore.setArchiveMode(true) },
    ],
  },

  {
    title: 'Logs',
    icon: { icon: 'tabler-code' },
    children: [
      { title: 'Utilisateurs', to: 'logs-users', onClick: () => archiveStore.setArchiveMode(false) },
      { title: 'Clients', to: 'logs-clients', onClick: () => archiveStore.setArchiveMode(false) },
      { title: 'Contacts', to: 'logs-contacts', onClick: () => archiveStore.setArchiveMode(false) },
      { title: 'Transactions', to: 'logs-transactions', onClick: () => archiveStore.setArchiveMode(false) },
      { title: 'Paiements', to: 'logs-payments', onClick: () => archiveStore.setArchiveMode(false) },
      { title: 'Demande de devis', to: 'logs-documents-quoterequests', onClick: () => archiveStore.setArchiveMode(false) },
      { title: 'Devis', to: 'logs-documents-quotes', onClick: () => archiveStore.setArchiveMode(false) },
      { title: 'Bons de commande', to: 'logs-documents-ordernotes', onClick: () => archiveStore.setArchiveMode(false) },
      { title: 'Bons de production', to: 'logs-documents-productionnotes', onClick: () => archiveStore.setArchiveMode(false) },
      { title: 'Bons de sortie', to: 'logs-documents-outputnotes', onClick: () => archiveStore.setArchiveMode(false) },
      { title: 'Bons de livraison', to: 'logs-documents-deliverynotes', onClick: () => archiveStore.setArchiveMode(false) },
      { title: 'Bons de retour', to: 'logs-documents-returnnotes', onClick: () => archiveStore.setArchiveMode(false) },
      { title: 'Factures', to: 'logs-documents-invoices', onClick: () => archiveStore.setArchiveMode(false) },
      { title: 'Reçus de commande', to: 'logs-documents-orderreceipts', onClick: () => archiveStore.setArchiveMode(false) },
      { title: 'Factures avoir', to: 'logs-documents-invoicecredits', onClick: () => archiveStore.setArchiveMode(false) },
      { title: 'Bons de remboursement', to: 'logs-documents-refunds', onClick: () => archiveStore.setArchiveMode(false) },
   ],
  },
   {
    title: 'Profil',
    icon: { icon: 'tabler-user-circle' },
    to: authStore.user ? { name: 'profil-id', params: { id: authStore.user.id } } : null,
    onClick: () => {
      archiveStore.setArchiveMode(false)
      if (authStore.user) {
        store.setEditMode(authStore.user);
        router.push({ name: 'profil-id', params: { id: authStore.user.id } });

      } else {
        console.warn('User not logged in');
      }
    },
  },
  {
    title: 'Se déconnecter',
    icon: { icon: 'tabler-logout' },
    to: { name: 'login' } ,
    onClick: () => {
      handleLogout();
    },
  },

]
