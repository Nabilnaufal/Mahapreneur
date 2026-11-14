/**
 * Seed Mahapreneur — data demo yang realistis untuk praktik E-Business.
 * Jalankan: npm run db:seed
 */
import { PrismaClient, type OrderStatus, type PaymentStatus } from '@prisma/client';
import bcrypt from 'bcrypt';

const prisma = new PrismaClient();
const img = (id: string) => `https://images.unsplash.com/${id}?auto=format&fit=crop&w=900&q=80`;

const CATEGORIES = [
  { name: 'Makanan', slug: 'makanan', icon: 'UtensilsCrossed', sortOrder: 1 },
  { name: 'Minuman', slug: 'minuman', icon: 'Coffee', sortOrder: 2 },
  { name: 'Buah-buahan', slug: 'buah-buahan', icon: 'Apple', sortOrder: 3 },
  { name: 'Fashion', slug: 'fashion', icon: 'Shirt', sortOrder: 4 },
  { name: 'Aksesoris', slug: 'aksesoris', icon: 'Watch', sortOrder: 5 },
  { name: 'Elektronik', slug: 'elektronik', icon: 'Laptop', sortOrder: 6 },
  { name: 'Jasa', slug: 'jasa', icon: 'Briefcase', sortOrder: 7 },
  { name: 'Lainnya', slug: 'lainnya', icon: 'Package', sortOrder: 8 },
];

async function main() {
  const password = await bcrypt.hash('Mahapreneur1', 10);
  const verified = new Date();

  const admin = await prisma.user.upsert({
    where: { email: 'admin@mahapreneur.test' },
    update: {},
    create: { name: 'Super Admin', email: 'admin@mahapreneur.test', password, role: 'SUPERADMIN', emailVerifiedAt: verified },
  });

  const categories: Record<string, number> = {};
  for (const c of CATEGORIES) {
    const row = await prisma.category.upsert({ where: { slug: c.slug }, update: {}, create: c });
    categories[c.slug] = row.id;
  }

  const sellers = [
    {
      email: 'dapur@mahapreneur.test', name: 'Rani Prameswari', phone: '081234567801',
      store: {
        name: 'Dapur Nusantara', slug: 'dapur-nusantara', campus: 'Gedung A - Fakultas Ekonomi',
        address: 'Jl. Kampus Raya No. 12, Depok', phone: '081234567801', openTime: '08:00', closeTime: '20:00',
        description: 'Masakan rumahan mahasiswa: porsi jujur, bumbu segar, harga bersahabat. Dimasak setiap pagi di dapur kos.',
        logo: img('photo-1556910103-1c02745aae4d'),
        banner: img('photo-1504674900247-0877df9cc836'),
      },
    },
    {
      email: 'kopi@mahapreneur.test', name: 'Bagas Adi Nugraha', phone: '081234567802',
      store: {
        name: 'Kopi Senja', slug: 'kopi-senja', campus: 'Perpustakaan Pusat',
        address: 'Jl. Melati No. 4, Depok', phone: '081234567802', openTime: '09:00', closeTime: '22:00',
        description: 'Kopi manual brew dan es kopi susu untuk menemani tugas akhir. Biji lokal Gayo dan Toraja.',
        logo: img('photo-1442512595331-e89e73853f31'),
        banner: img('photo-1495474472287-4d71bcdd2085'),
      },
    },
    {
      email: 'jasa@mahapreneur.test', name: 'Nadia Kusuma', phone: '081234567803',
      store: {
        name: 'Studio Nadia', slug: 'studio-nadia', campus: 'Gedung C - Fakultas Ilmu Komputer',
        address: 'Jl. Kenanga No. 8, Depok', phone: '081234567803', openTime: '10:00', closeTime: '21:00',
        description: 'Jasa desain grafis dan editing video untuk kebutuhan akademik maupun bisnis kecil. Dikerjakan sendiri oleh mahasiswa DKV.',
        logo: img('photo-1561070791-2526d30994b5'),
        banner: img('photo-1521737604893-d14cc237f11d'),
      },
    },
  ];

  const stores: Record<string, number> = {};
  const sellerIds: Record<string, number> = {};

  for (const s of sellers) {
    const user = await prisma.user.upsert({
      where: { email: s.email },
      update: {},
      create: { name: s.name, email: s.email, password, role: 'SELLER', emailVerifiedAt: verified, phone: s.phone },
    });
    sellerIds[s.store.slug] = user.id;
    const store = await prisma.store.upsert({
      where: { slug: s.store.slug },
      update: { logo: s.store.logo, banner: s.store.banner },
      create: { ...s.store, userId: user.id },
    });
    stores[s.store.slug] = store.id;
  }

  const customer = await prisma.user.upsert({
    where: { email: 'pembeli@mahapreneur.test' },
    update: {},
    create: {
      name: 'Alya Putri', email: 'pembeli@mahapreneur.test', password, role: 'CUSTOMER',
      emailVerifiedAt: verified, phone: '081298765432',
    },
  });

  const products: {
    store: string; category: string; name: string; price: number; stock: number; sold?: number;
    type?: 'GOODS' | 'SERVICE'; description: string; images: string[];
    serviceTerms?: string; serviceDuration?: string;
  }[] = [
    {
      store: 'dapur-nusantara', category: 'makanan', name: 'Nasi Ayam Geprek Sambal Bawang', price: 18000, stock: 40, sold: 26,
      description: 'Ayam goreng tepung renyah dengan sambal bawang pedas segar, nasi hangat, dan lalapan. Porsi mahasiswa, bisa minta level pedas 1–5.',
      images: [img('photo-1565299624946-b28f40a0ae38'), img('photo-1555939594-58d7cb561ad1')],
    },
    {
      store: 'dapur-nusantara', category: 'makanan', name: 'Rice Bowl Telur Sambal Matah', price: 15000, stock: 30, sold: 18,
      description: 'Rice bowl isi telur mata sapi, sambal matah segar, dan sayuran tumis. Cocok untuk makan siang di sela kelas.',
      images: [img('photo-1546069901-ba9599a7e63c')],
    },
    {
      store: 'dapur-nusantara', category: 'makanan', name: 'Paket Katering Mingguan (5 Hari)', price: 165000, stock: 8, sold: 3,
      description: 'Lima kotak makan untuk Senin–Jumat, dikirim ke kelas atau kos setiap pagi. Menu berganti tiap hari dan bisa diatur sesuai selera.',
      images: [img('photo-1512621776951-a57141f2eefd')],
    },
    {
      store: 'dapur-nusantara', category: 'buah-buahan', name: 'Salad Buah Segar 350ml', price: 22000, stock: 12, sold: 9,
      description: 'Potongan melon, semangka, anggur, dan stroberi dengan yogurt dressing tanpa gula tambahan.',
      images: [img('photo-1610832958506-aa56368176cf')],
    },
    {
      store: 'kopi-senja', category: 'minuman', name: 'Es Kopi Susu Senja', price: 17000, stock: 50, sold: 41,
      description: 'Espresso double shot, susu segar, dan gula aren cair. Rasanya seimbang, tidak terlalu manis.',
      images: [img('photo-1461023058943-07fcbe16d735')],
    },
    {
      store: 'kopi-senja', category: 'minuman', name: 'Manual Brew Gayo V60', price: 24000, stock: 20, sold: 12,
      description: 'Single origin Gayo, medium roast, diseduh V60 saat dipesan. Notes: jeruk, gula palem, aftertaste bersih.',
      images: [img('photo-1495474472287-4d71bcdd2085')],
    },
    {
      store: 'kopi-senja', category: 'minuman', name: 'Biji Kopi Toraja 200gr', price: 78000, stock: 15, sold: 6,
      description: 'Biji kopi Toraja sangrai medium, dikemas 200 gram dengan valve. Bisa digiling sesuai permintaan.',
      images: [img('photo-1447933601403-0c6688de566e')],
    },
    {
      store: 'kopi-senja', category: 'minuman', name: 'Es Teh Lemon Mint', price: 12000, stock: 35, sold: 22,
      description: 'Teh hitam dingin dengan perasan lemon segar dan daun mint. Pilihan ringan di siang hari.',
      images: [img('photo-1517701550927-30cf4ba1dba5')],
    },
    {
      store: 'studio-nadia', category: 'jasa', name: 'Desain Logo Usaha Mahasiswa', price: 150000, stock: 10, sold: 7, type: 'SERVICE',
      description: 'Desain logo custom untuk usaha kecil mahasiswa, termasuk 2 kali revisi dan file final (PNG, JPG, SVG).',
      serviceTerms: 'Pemesanan minimal H-3. Revisi maksimal 2 kali. File sumber dikirim setelah pembayaran diterima.',
      serviceDuration: '2–3 hari kerja',
      images: [img('photo-1561070791-2526d30994b5')],
    },
    {
      store: 'studio-nadia', category: 'jasa', name: 'Editing Video Presentasi 3 Menit', price: 200000, stock: 6, sold: 2, type: 'SERVICE',
      description: 'Editing video presentasi atau tugas kuliah hingga 3 menit: cut, transisi, subtitle, dan color grading ringan.',
      serviceTerms: 'Kirim materi mentah dalam format MP4. Revisi maksimal 2 kali. Durasi lebih dari 3 menit dikenakan biaya tambahan.',
      serviceDuration: '3–4 hari kerja',
      images: [img('photo-1521737604893-d14cc237f11d')],
    },
    {
      store: 'studio-nadia', category: 'jasa', name: 'Jasa Desain Feed Instagram (5 Post)', price: 175000, stock: 8, sold: 4, type: 'SERVICE',
      description: 'Lima desain feed Instagram dengan template konsisten, cocok untuk UMKM mahasiswa yang baru mulai.',
      serviceTerms: 'Konten dan foto disediakan pemesan. Revisi 1 kali per desain. File dikirim dalam PNG dan Canva template.',
      serviceDuration: '3 hari kerja',
      images: [img('photo-1561070791-2526d30994b5')],
    },
    {
      store: 'studio-nadia', category: 'lainnya', name: 'Paket CV ATS-Friendly', price: 65000, stock: 25, sold: 11, type: 'SERVICE',
      description: 'Penulisan ulang dan desain CV yang lolos sistem ATS, siap dipakai untuk magang dan pekerjaan pertama.',
      serviceTerms: 'Pemesan mengirim CV lama dan deskripsi posisi yang dituju. Revisi 1 kali.',
      serviceDuration: '1–2 hari kerja',
      images: [img('photo-1498049794561-7780e7231661')],
    },
  ];

  const productIds: number[] = [];
  for (const p of products) {
    const slug = p.name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
    const existing = await prisma.product.findUnique({ where: { slug } });
    if (existing) {
      productIds.push(existing.id);
      continue;
    }
    const created = await prisma.product.create({
      data: {
        storeId: stores[p.store],
        categoryId: categories[p.category],
        name: p.name, slug, description: p.description, price: p.price, stock: p.stock,
        type: p.type ?? 'GOODS', status: 'ACTIVE', sold: p.sold ?? 0,
        serviceTerms: p.serviceTerms, serviceDuration: p.serviceDuration,
        images: { create: p.images.map((url, i) => ({ url, sortOrder: i })) },
      },
    });
    productIds.push(created.id);
  }

  const bannerCount = await prisma.banner.count();
  if (bannerCount === 0) {
    await prisma.banner.createMany({
      data: [
        {
          title: 'Belanja dari tetangga kampusmu.',
          description: 'Makanan, minuman, dan jasa dari mahasiswa lain — diantar langsung oleh seller ke kelas atau kosmu.',
          image: img('photo-1504674900247-0877df9cc836'),
          ctaLabel: 'Jelajahi Produk', ctaUrl: '/catalog', sortOrder: 1,
        },
        {
          title: 'Buka toko pertamamu di Mahapreneur.',
          description: 'Daftar sebagai seller, unggah produk, dan mulai terima pesanan dari mahasiswa se-kampus.',
          image: img('photo-1521737604893-d14cc237f11d'),
          ctaLabel: 'Mulai Berjualan', ctaUrl: '/auth/register?role=SELLER', sortOrder: 2,
        },
        {
          title: 'Bayar fleksibel: QRIS, VA, atau COD.',
          description: 'Pilih pembayaran online lewat DOKU atau bayar langsung saat pesanan diterima.',
          image: img('photo-1556742049-0cfed4f6a45d'),
          ctaLabel: 'Cara Berbelanja', ctaUrl: '/catalog', sortOrder: 3,
        },
      ],
    });
  }

  // Satu pesanan selesai (COD) agar dashboard seller & admin punya data nyata.
  const existingOrder = await prisma.order.findFirst({ where: { customerId: customer.id } });
  if (!existingOrder) {
    const order = await prisma.order.create({
      data: {
        orderNumber: 'MP-260101-1001',
        customerId: customer.id,
        storeId: stores['dapur-nusantara'],
        status: 'COMPLETED' as OrderStatus,
        paymentStatus: 'PAID' as PaymentStatus,
        paymentMethod: 'COD',
        subtotal: 33000, total: 33000,
        recipientName: 'Alya Putri', recipientPhone: '081298765432',
        address: 'Kos Melati, Jl. Melati No. 21, Depok', campus: 'Gedung A - Fakultas Ekonomi', room: 'Ruang 204',
        note: 'Tolong antar sebelum jam 12 siang, ada kelas.',
        confirmedAt: new Date(Date.now() - 5 * 86400000),
        completedAt: new Date(Date.now() - 4 * 86400000),
        items: {
          create: [
            { productId: productIds[0], productName: 'Nasi Ayam Geprek Sambal Bawang', productPrice: 18000, quantity: 1, subtotal: 18000 },
            { productId: productIds[1], productName: 'Rice Bowl Telur Sambal Matah', productPrice: 15000, quantity: 1, subtotal: 15000 },
          ],
        },
        payment: { create: { method: 'COD', status: 'PAID', amount: 33000, paidAt: new Date(Date.now() - 4 * 86400000) } },
      },
    });
    await prisma.orderStatusHistory.createMany({
      data: [
        { orderId: order.id, status: 'CONFIRMED', note: 'Pesanan COD dibuat' },
        { orderId: order.id, status: 'PROCESSING', note: 'Pesanan mulai dimasak' },
        { orderId: order.id, status: 'ON_DELIVERY', note: 'Diantar ke Gedung A' },
        { orderId: order.id, status: 'COMPLETED', note: 'Diterima Alya, pembayaran COD lunas' },
      ],
    });
    await prisma.paymentNotification.create({
      data: { notificationKey: 'seed-cod-completed', orderNumber: order.orderNumber, transactionStatus: 'COD_PAID', amount: 33000, signatureValid: true, processed: true, note: 'Data seed' },
    });

    await prisma.notification.createMany({
      data: [
        { userId: customer.id, title: 'Pesanan selesai', message: `Pesanan ${order.orderNumber} sudah kamu terima. Jangan lupa beri tahu seller jika ada kendala.`, type: 'success', link: `/customer/orders/${order.id}` },
        { userId: sellerIds['dapur-nusantara'], title: 'Pesanan COD lunas', message: `Pembayaran pesanan ${order.orderNumber} sudah diterima.`, type: 'success', link: `/seller/orders/${order.id}` },
      ],
    });
  }

  await prisma.activityLog.create({
    data: { userId: admin.id, action: 'SEED', entity: 'System', description: 'Data demo Mahapreneur dimuat' },
  });

  console.log('Seed selesai.');
  console.log('Superadmin : admin@mahapreneur.test / Mahapreneur1');
  console.log('Seller     : dapur@mahapreneur.test / kopi@mahapreneur.test / jasa@mahapreneur.test (password sama)');
  console.log('Customer   : pembeli@mahapreneur.test / Mahapreneur1');
}

main()
  .catch((e) => {
    console.error(e);
    process.exit(1);
  })
  .finally(() => prisma.$disconnect());
