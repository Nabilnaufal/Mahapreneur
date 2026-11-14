import { Router } from 'express';
import { authController } from '../controllers/auth.controller.js';
import { authenticate } from '../middleware/auth.js';
import { ah } from '../utils/http.js';

export const authRoutes = Router();

authRoutes.post('/register', ah(authController.register));
authRoutes.post('/verify-email', ah(authController.verifyEmail));
authRoutes.post('/resend-verification', ah(authController.resendVerification));
authRoutes.post('/login', ah(authController.login));
authRoutes.post('/logout', ah(authController.logout));
authRoutes.get('/me', authenticate, ah(authController.me));
authRoutes.post('/forgot-password', ah(authController.forgotPassword));
authRoutes.post('/reset-password', ah(authController.resetPassword));
