import { Router } from 'express';
import { adminRoutes } from './admin.routes.js';
import { authRoutes } from './auth.routes.js';
import { customerRoutes } from './customer.routes.js';
import { notificationRoutes } from './notification.routes.js';
import { paymentRoutes } from './payment.routes.js';
import { publicRoutes } from './public.routes.js';
import { sellerRoutes } from './seller.routes.js';

export const apiRoutes = Router();

apiRoutes.use('/auth', authRoutes);
apiRoutes.use('/public', publicRoutes);
apiRoutes.use('/payments', paymentRoutes);
apiRoutes.use('/customer', customerRoutes);
apiRoutes.use('/notifications', notificationRoutes);
apiRoutes.use('/seller', sellerRoutes);
apiRoutes.use('/admin', adminRoutes);
