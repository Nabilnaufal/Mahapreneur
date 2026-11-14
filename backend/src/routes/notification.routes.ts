import { Router } from 'express';
import { customerController } from '../controllers/customer.controller.js';
import { authenticate } from '../middleware/auth.js';
import { ah } from '../utils/http.js';

/** Notifikasi milik pengguna yang sedang masuk — dipakai semua role. */
export const notificationRoutes = Router();

notificationRoutes.use(authenticate);
notificationRoutes.get('/', ah(customerController.notifications));
notificationRoutes.patch('/read-all', ah(customerController.readAllNotifications));
notificationRoutes.patch('/:id/read', ah(customerController.readNotification));