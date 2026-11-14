import { Router } from 'express';
import { adminController } from '../controllers/admin.controller.js';
import { paymentController } from '../controllers/payment.controller.js';
import { authenticate, requireRole } from '../middleware/auth.js';
import { folder, upload } from '../middleware/upload.js';
import { ah } from '../utils/http.js';

export const adminRoutes = Router();

adminRoutes.use(authenticate, requireRole('SUPERADMIN'));

adminRoutes.get('/dashboard', ah(adminController.dashboard));

adminRoutes.get('/users', ah(adminController.listUsers));
adminRoutes.get('/users/:id', ah(adminController.getUser));
adminRoutes.patch('/users/:id/status', ah(adminController.setUserStatus));

adminRoutes.get('/categories', ah(adminController.listCategories));
adminRoutes.post('/categories', ah(adminController.createCategory));
adminRoutes.patch('/categories/:id', ah(adminController.updateCategory));
adminRoutes.delete('/categories/:id', ah(adminController.deleteCategory));

adminRoutes.get('/stores', ah(adminController.listStores));
adminRoutes.patch('/stores/:id/status', ah(adminController.setStoreStatus));

adminRoutes.get('/products', ah(adminController.listProducts));
adminRoutes.delete('/products/:id', ah(adminController.deleteProduct));

adminRoutes.get('/orders', ah(adminController.listOrders));
adminRoutes.get('/orders/:id', ah(adminController.getOrder));
adminRoutes.get('/payments', ah(adminController.listPayments));
adminRoutes.get('/payment-notifications', ah(paymentController.notifications));

adminRoutes.get('/banners', ah(adminController.listBanners));
adminRoutes.post('/banners', ah(adminController.createBanner));
adminRoutes.patch('/banners/:id', ah(adminController.updateBanner));
adminRoutes.delete('/banners/:id', ah(adminController.deleteBanner));
adminRoutes.post('/banners/upload', folder('banners'), upload.single('image'), ah(adminController.uploadBannerImage));

adminRoutes.get('/activity', ah(adminController.activity));
adminRoutes.get('/notifications', ah(adminController.notifications));
