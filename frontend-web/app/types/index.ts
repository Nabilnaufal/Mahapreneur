// Tipe bersama yang mencerminkan bentuk respons REST API Mahapreneur.

export type Role = 'SUPERADMIN' | 'SELLER' | 'CUSTOMER';
export type ProductType = 'GOODS' | 'SERVICE';
export type ProductStatus = 'ACTIVE' | 'INACTIVE';
export type StoreStatus = 'ACTIVE' | 'INACTIVE' | 'SUSPENDED';
export type OrderStatus =
  | 'WAITING_PAYMENT'
  | 'CONFIRMED'
  | 'PROCESSING'
  | 'READY'
  | 'ON_DELIVERY'
  | 'COMPLETED'
  | 'CANCELLED';
export type PaymentStatus = 'PENDING' | 'PAID' | 'FAILED' | 'EXPIRED' | 'UNPAID_COD' | 'REFUNDED';
export type PaymentMethod = 'QRIS' | 'VIRTUAL_ACCOUNT' | 'EWALLET' | 'COD';

export interface User {
  id: number;
  name: string;
  email: string;
  role: Role;
  isActive: boolean;
  emailVerifiedAt: string | null;
  avatar: string | null;
  phone: string | null;
  createdAt: string;
  store?: { id: number; name: string; slug: string; status: StoreStatus; logo: string | null } | null;
  _count?: { orders: number };
}

export interface Category {
  id: number;
  name: string;
  slug: string;
  icon: string | null;
  sortOrder: number;
  isActive: boolean;
  productCount?: number;
}

export interface Store {
  id: number;
  name: string;
  slug: string;
  description: string | null;
  logo: string | null;
  banner: string | null;
  phone: string | null;
  email: string | null;
  address: string | null;
  campus: string | null;
  openTime: string | null;
  closeTime: string | null;
  status: StoreStatus;
  productCount?: number;
  orderCount?: number;
  rating?: number | null;
  ratingCount?: number;
  owner?: { id: number; name: string; email: string; isActive?: boolean };
  user?: { name: string; createdAt: string };
  _count?: { products: number };
}

export interface Product {
  id: number;
  name: string;
  slug: string;
  description: string;
  price: number;
  stock: number;
  type: ProductType;
  status: ProductStatus;
  sold: number;
  rating?: number | null;
  ratingCount?: number;
  serviceTerms: string | null;
  serviceDuration: string | null;
  createdAt: string;
  image: string | null;
  images: string[];
  imageRecords?: { id: number; url: string }[];
  category: { id: number; name: string; slug: string; icon: string | null } | null;
  store: { id: number; name: string; slug: string; logo: string | null } | null;
  reviews?: ProductReview[];
}

export interface ProductReview {
  id: number;
  rating: number;
  comment: string | null;
  createdAt: string;
  customer: { name: string } | null;
}

export interface Banner {
  id: number;
  title: string;
  description: string | null;
  image: string;
  ctaLabel: string | null;
  ctaUrl: string | null;
  sortOrder: number;
  status: 'ACTIVE' | 'INACTIVE';
}

export interface CartItem {
  id: number;
  quantity: number;
  subtotal: number;
  product: Product;
}

export interface CartResponse {
  items: CartItem[];
  summary: { itemCount: number; storeCount: number; subtotal: number };
}

export interface OrderItem {
  id: number;
  productId: number | null;
  productName: string;
  productPrice: number;
  quantity: number;
  subtotal: number;
  image?: string | null;
}

export interface OrderReview {
  id: number;
  rating: number;
  comment: string | null;
  createdAt: string;
  updatedAt: string;
}

export interface Payment {
  id: number;
  method: PaymentMethod;
  status: PaymentStatus;
  amount: number;
  invoiceNumber: string | null;
  dokuPaymentUrl: string | null;
  dokuQrString: string | null;
  dokuVaNumber: string | null;
  dokuChannel: string | null;
  expiresAt: string | null;
  paidAt: string | null;
}

export interface Order {
  id: number;
  orderNumber: string;
  status: OrderStatus;
  paymentStatus: PaymentStatus;
  paymentMethod: PaymentMethod;
  subtotal: number;
  total: number;
  recipientName: string;
  recipientPhone: string;
  address: string;
  campus: string | null;
  room: string | null;
  note: string | null;
  createdAt: string;
  store?: Store;
  customer?: { id?: number; name: string; email: string; phone?: string | null };
  items: OrderItem[];
  payment: Payment | null;
  histories?: { id: number; status: OrderStatus; note: string | null; createdAt: string }[];
  review?: OrderReview | null;
}

export interface Address {
  id: number;
  label: string;
  recipientName: string;
  phone: string;
  address: string;
  campus: string | null;
  room: string | null;
  isDefault: boolean;
}

export interface Notification {
  id: number;
  title: string;
  message: string;
  type: string;
  link: string | null;
  isRead: boolean;
  createdAt: string;
}

export interface ChatMessage {
  id: number;
  conversationId: number;
  body: string;
  senderId: number;
  senderName?: string | null;
  readAt: string | null;
  createdAt: string;
}

export interface ChatConversation {
  id: number;
  customerId: number | null;
  storeId: number | null;
  store?: { id: number; name: string; slug: string; logo: string | null } | null;
  customer?: { id: number; name: string; avatar: string | null } | null;
  seller?: { id: number; name: string; avatar: string | null } | null;
  lastMessage: ChatMessage | null;
  unreadCount: number;
  lastMessageAt: string | null;
}

export interface PaginationMeta {
  total: number;
  page: number;
  limit: number;
  totalPages: number;
}

export interface ActivityLog {
  id: number;
  action: string;
  entity: string;
  entityId: string | null;
  description: string;
  createdAt: string;
  user?: { id: number; name: string; email: string; role: Role } | null;
}

export interface PlatformFeedback {
  id: number;
  rating: number;
  comment: string;
  createdAt: string;
  user?: { id: number; name: string; email: string; role: Role } | null;
}

export interface PaymentNotification {
  id: number;
  notificationKey: string;
  invoiceNumber: string | null;
  orderNumber: string | null;
  transactionStatus: string | null;
  amount: number | null;
  signatureValid: boolean;
  processed: boolean;
  note: string | null;
  createdAt: string;
}
