/**
 * seed.js — MongoDB Seed Data for FUNDBEE Index Page
 *
 * Run this in MongoDB Shell (mongosh) or MongoDB Compass Shell:
 *   mongosh fundbee_db seed.js
 *
 * This populates the 3 collections used by the index page backend:
 *   1. site_stats        → Homepage stats band
 *   2. loan_products     → Services section
 *   3. (contact_inquiries & newsletter_subscribers are auto-created on first POST)
 */

// ── Switch to database ──
use('fundbee_db');

// ══════════════════════════════════════════════
// 1. SITE STATS  (shown in the stats-band)
// ══════════════════════════════════════════════
db.site_stats.drop();

db.site_stats.insertMany([
  {
    stat_key    : 'experience',
    stat_value  : '10+',
    stat_label  : 'Years of Experience',
    display_order: 1
  },
  {
    stat_key    : 'downloads',
    stat_value  : '2M+',
    stat_label  : 'App Downloads',
    display_order: 2
  },
  {
    stat_key    : 'loans',
    stat_value  : '50K+',
    stat_label  : 'Loans Approved',
    display_order: 3
  },
  {
    stat_key    : 'customers',
    stat_value  : '25K+',
    stat_label  : 'Happy Customers',
    display_order: 4
  }
]);

print('✅ site_stats seeded: ' + db.site_stats.countDocuments() + ' records');


// ══════════════════════════════════════════════
// 2. LOAN PRODUCTS  (shown in the services grid)
// ══════════════════════════════════════════════
db.loan_products.drop();

db.loan_products.insertMany([
  {
    name          : 'Personal Loan',
    icon          : '👤',
    description   : 'Instant approvals with minimal documentation. Fund your dreams without the wait.',
    interest_rate : 'Starting at 10.5% p.a.',
    badge         : 'Popular',
    image_path    : '/Frontend/images/img2.jpg',
    is_active     : true,
    display_order : 1
  },
  {
    name          : 'Business Loan',
    icon          : '🏢',
    description   : 'Fuel your enterprise growth with tailored capital and flexible repayment options.',
    interest_rate : 'Starting at 12% p.a.',
    badge         : null,
    image_path    : '/Frontend/images/img3.jpg',
    is_active     : true,
    display_order : 2
  },
  {
    name          : 'Home Loan',
    icon          : '🏠',
    description   : 'Make your home ownership dream a reality with competitive rates and long tenures.',
    interest_rate : 'Starting at 8.5% p.a.',
    badge         : 'Low Rate',
    image_path    : '/Frontend/images/img4.jpg',
    is_active     : true,
    display_order : 3
  },
  {
    name          : 'Instant Loan',
    icon          : '⚡',
    description   : 'Disbursal in under 24 hours. For those moments when every minute counts.',
    interest_rate : 'Starting at 14% p.a.',
    badge         : null,
    image_path    : '/Frontend/images/bank.jpg',
    is_active     : true,
    display_order : 4
  }
]);

print('✅ loan_products seeded: ' + db.loan_products.countDocuments() + ' records');


// ══════════════════════════════════════════════
// 3. CREATE INDEXES  (for performance)
// ══════════════════════════════════════════════

// Unique index on email in newsletter subscribers
db.newsletter_subscribers.createIndex({ email: 1 }, { unique: true });
print('✅ newsletter_subscribers index created');

// Index on email + phone for contact inquiries lookup
db.contact_inquiries.createIndex({ email: 1 });
db.contact_inquiries.createIndex({ created_at: -1 });
print('✅ contact_inquiries indexes created');

print('\n🎉 All seed data inserted successfully into fundbee_db!');
