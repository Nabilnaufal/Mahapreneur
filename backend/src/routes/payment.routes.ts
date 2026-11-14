import { Router } from 'express';
import { paymentController } from '../controllers/payment.controller.js';
import { ah } from '../utils/http.js';

export const paymentRoutes = Router();

// Dipakai halaman checkout untuk menampilkan metode pembayaran yang tersedia.
paymentRoutes.get('/methods', ah(paymentController.methods));

// Callback resmi DOKU. Tidak pernah mengubah status tanpa verifikasi signature.
paymentRoutes.post('/doku/notification', ah(paymentController.dokuNotification));
