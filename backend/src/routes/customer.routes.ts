import { Router } from 'express';
import { customerController } from '../controllers/customer.controller.js';
import { authenticate, requireRole } from '../middleware/auth.js';
import { ah } from '../utils/http.js';

export const customerRoutes = Router();

// Seluruh endpoint di sini hanya untuk customer yang sudah masuk.
customerRoutes.use(authenticate, requireRole('CUSTOMER'));

customerRoutes.get('/cart', ah(customerController.getCart));
customerRoutes.post('/cart', ah(customerController.addToCart));
customerRoutes.patch('/cart/:itemId', ah(customerController.updateCartItem));
customerRoutes.delete('/cart/:itemId', ah(customerController.removeCartItem));

customerRoutes.get('/wishlist', ah(customerController.getWishlist));
customerRoutes.post('/wishlist', ah(customerController.addWishlist));
customerRoutes.delete('/wishlist/:productId', ah(customerController.removeWishlist));

customerRoutes.get('/addresses', ah(customerController.listAddresses));
customerRoutes.post('/addresses', ah(customerController.createAddress));
customerRoutes.patch('/addresses/:id', ah(customerController.updateAddress));
customerRoutes.delete('/addresses/:id', ah(customerController.deleteAddress));

customerRoutes.post('/checkout', ah(customerController.checkout));
customerRoutes.get('/orders', ah(customerController.listOrders));
customerRoutes.get('/orders/:id', ah(customerController.getOrder));
customerRoutes.post('/orders/:id/cancel', ah(customerController.cancelOrder));

customerRoutes.get('/orders/:id/payment', ah(customerController.getPayment));
customerRoutes.post('/orders/:id/payment/channel', ah(customerController.choosePaymentChannel));
customerRoutes.post('/orders/:id/payment/retry', ah(customerController.retryPayment));
customerRoutes.post('/orders/:id/payment/simulate', ah(customerController.simulatePayment));

customerRoutes.get('/profile', ah(customerController.getProfile));
customerRoutes.patch('/profile', ah(customerController.updateProfile));
customerRoutes.patch('/profile/password', ah(customerController.changePassword));
