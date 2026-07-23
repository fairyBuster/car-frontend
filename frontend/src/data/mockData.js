const clone = (value) => JSON.parse(JSON.stringify(value))

const now = Date.now()
const futureIso = (seconds) => new Date(now + seconds * 1000).toISOString()

export const marketNoticeItems = [
  { key: 'neon-tetra', name: 'Neon Tetra', market_available_at: futureIso(15) },
  { key: 'angelfish', name: 'Angelfish', market_available_at: futureIso(55) },
]

export const defaultHeaderActions = [
  {
    key: 'support',
    iconName: 'support',
    ariaLabel: 'Support center',
  },
  { type: 'button', key: 'language', iconName: 'language', ariaLabel: 'Language' },
]

export const backProfileHeaderActions = [
  { to: '/m/pages/dashboard', iconName: 'back', ariaLabel: 'Return to home' },
  { to: '/m/pages/profile', iconName: 'profile', ariaLabel: 'Profile' },
]

export const backLogoutHeaderActions = [
  { to: '/m/pages/dashboard', iconName: 'back', ariaLabel: 'Back to dashboard' },
  { type: 'button', key: 'logout', iconName: 'logout', ariaLabel: 'Log Out', label: 'Log Out', className: 'profile-logout-btn' },
]

export const standardFooterItems = [
  { key: 'home', label: 'Menu Rumah', to: '/m/pages/dashboard', iconName: 'home' },
  { key: 'aquarium', label: 'Kargo', to: '/m/pages/cargo', iconName: 'aquarium' },
  { key: 'invite', label: 'Undang Teman', to: '/m/pages/invite', iconName: 'invite' },
  { key: 'profile', label: 'Profil', to: '/m/pages/profile', iconName: 'profile' },
]

export const marketFooterItems = [
  { key: 'home', label: 'Home', to: '/m/pages/dashboard', iconName: 'home' },
  { key: 'aquarium', label: 'Aquarium', to: '/m/pages/cargo', iconName: 'aquarium' },
  { key: 'market', label: 'Market', to: '/m/pages/mycargo', iconName: 'market' },
  { key: 'profile', label: 'Profile', to: '/m/pages/profile', iconName: 'profile' },
]

export const infoFooterItems = [
  { key: 'home', label: 'Home', to: '/m/pages/dashboard', iconName: 'home' },
  { key: 'shop', label: 'Shop', href: '#', iconName: 'shop' },
  { key: 'invite', label: 'Invite', to: '/m/pages/invite', iconName: 'invite' },
  { key: 'my', label: 'My', href: '#', iconName: 'my' },
]

export const dashboardMenuItems = [
  {
    label: 'Security',
    to: '/m/pages/policy',
    tone: 'tone-blue',
    icon: '<svg viewBox="0 0 24 24"><path d="M12 3.5l7 3v5.2c0 4.1-2.8 7.8-7 8.8-4.2-1-7-4.7-7-8.8V6.5z"></path><path d="M9.5 12l1.7 1.7 3.3-3.7"></path></svg>',
  },
  {
    label: 'Company',
    to: '/m/pages/company',
    tone: 'tone-green',
    icon: '<svg viewBox="0 0 24 24"><path d="M5 19h14"></path><path d="M7.5 19V8.5"></path><path d="M12 19V5"></path><path d="M16.5 19v-7"></path><path d="M5 8.5h5"></path><path d="M12 5h4.5"></path></svg>',
  },
  {
    label: 'Ikat bank',
    to: '/m/pages/wallet',
    tone: 'tone-blue',
    icon: '<svg viewBox="0 0 24 24"><rect x="5" y="4.5" width="14" height="15" rx="2"></rect><path d="M9 8.5h6"></path><path d="M12 15V9"></path><path d="M9.5 12.5L12 15l2.5-2.5"></path></svg>',
  },
  {
    label: 'Undangan',
    to: '/m/pages/invite',
    tone: 'tone-green',
    icon: '<svg viewBox="0 0 24 24"><circle cx="8.5" cy="8" r="2.3"></circle><circle cx="15.5" cy="9.5" r="1.9"></circle><path d="M4.5 18.5c0-2.6 2.1-4.5 4.7-4.5 1.5 0 2.9.6 3.8 1.7"></path><path d="M13 16.5h6"></path><path d="M16 13.5v6"></path></svg>',
  },
  {
    label: 'Deposit',
    to: '/m/pages/addbalance',
    tone: 'tone-blue',
    icon: '<svg viewBox="0 0 24 24"><rect x="4" y="14" width="16" height="5.5" rx="1.6"></rect><path d="M12 4v10"></path><path d="M8.5 10.5L12 14l3.5-3.5"></path></svg>',
  },
  {
    label: 'Withdraw',
    to: '/m/pages/settle',
    tone: 'tone-green',
    icon: '<svg viewBox="0 0 24 24"><rect x="4" y="14" width="16" height="5.5" rx="1.6"></rect><path d="M12 19V9"></path><path d="M8.5 12.5L12 9l3.5 3.5"></path></svg>',
  },
  {
    label: 'Support',
    type: 'button',
    tone: 'tone-green',
    icon: '<svg viewBox="0 0 24 24"><path d="M21 5L3.8 11.6l5.2 2.1L18.2 7 11 14.6V19l3.2-3.1 4.4 2.9L21 5z"></path></svg>',
  },
  {
    label: 'Milik saya',
    to: '/m/pages/mycargo',
    tone: 'tone-blue',
    icon: '<svg viewBox="0 0 24 24"><path d="M4 8h16l-1.5 3H5.5z"></path><path d="M6 11v6h12v-6"></path><path d="M9 14c1.1-1 2.1-1 3.2 0 1.1 1 2.1 1 3.1 0"></path></svg>',
  },
  {
    label: 'Team',
    to: '/m/pages/myteam',
    tone: 'tone-blue',
    icon: '<svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="3"></circle><circle cx="6.8" cy="10.2" r="2.1"></circle><circle cx="17.2" cy="10.2" r="2.1"></circle><path d="M8 18.5c0-2.1 1.8-3.9 4-3.9s4 1.8 4 3.9"></path><path d="M3.8 18.5c0-1.6 1.4-2.9 3-2.9"></path><path d="M20.2 18.5c0-1.6-1.4-2.9-3-2.9"></path></svg>',
  },
  {
    label: 'Berita',
    to: '/m/pages/news',
    tone: 'tone-green',
    icon: '<svg viewBox="0 0 24 24"><rect x="4" y="5" width="16" height="14" rx="2"></rect><path d="M7 9h10M7 12h6M7 15h7"></path><circle cx="16.5" cy="13.5" r="1.8"></circle></svg>',
  },
]

export const dashboardFishList = [
  {
    key: 'guppy',
    name: 'Guppy',
    image: '/akvaryum/Guppy.webp',
    imageAlt: 'Guppy fish',
    labels: ['Freshwater', 'Peaceful'],
    unlockRule: 'Open at sign-up.',
    rate: '4.00%',
    size: '3-5 cm',
    care: 'Easy',
    mapHash: '#map-guppy',
  },
  {
    key: 'neon-tetra',
    name: 'Neon Tetra',
    image: '/akvaryum/neontera.jpg',
    imageAlt: 'Neon Tetra fish',
    labels: ['Amazon', 'Schooling'],
    unlockRule: 'Rp30+ balance • ',
    rate: '2.70%',
    size: '3-4 cm',
    care: 'Easy',
    mapHash: '#map-neon-tetra',
  },
  {
    key: 'lepistes',
    name: 'Troud',
    image: '/akvaryum/troud.jpg',
    imageAlt: 'Lepistes fish',
    labels: ['Freshwater', 'Hardy'],
    unlockRule: 'Rp80+ balance • ',
    rate: '3.00%',
    size: '3-5 cm',
    care: 'Medium',
    mapHash: '#map-lepistes',
  },
  {
    key: 'angelfish',
    name: 'Angelfish',
    image: '/akvaryum/melek-baligi.jpg',
    imageAlt: 'Angelfish',
    labels: ['Freshwater', 'Semi-peaceful'],
    unlockRule: 'Rp150+ balance • ',
    rate: '3.50%',
    size: '10–15 cm',
    care: 'Medium',
    mapHash: '#map-angelfish',
  },
  {
    key: 'discus',
    name: 'Discus Fish',
    image: '/akvaryum/diskus.jpg',
    imageAlt: 'Discus Fish',
    labels: ['Freshwater', 'Peaceful'],
    unlockRule: 'Rp250+ balance • 2 referrals',
    rate: '2.20%',
    size: '15–20 cm',
    care: 'Hard',
    mapHash: '#map-discus',
  },
  {
    key: 'oscar',
    name: 'Oscar Fish',
    image: '/akvaryum/oscar-baligi.webp',
    imageAlt: 'Oscar Fish',
    labels: ['Freshwater', 'Semi-aggressive'],
    unlockRule: 'Rp400+ balance • 4 referrals',
    rate: '2.00%',
    size: '25–35 cm',
    care: 'Hard',
    mapHash: '#map-oscar',
  },
]

const fishFoodCards = [
  {
    key: 'guppy',
    name: 'Guppy',
    image: '/akvaryum/Guppy.webp',
    imageAlt: 'Guppy fish',
    tags: ['Freshwater', 'Starter'],
    levelKey: 'starter',
    levelLabel: 'Level 1',
    isUnlocked: true,
    referralRequirement: 0,
    rewardDays: 5,
    meetsBalance: true,
    meetsReferrals: true,
    unlockBalance: '5',
    bonusDays: 10,
    unlockMessage: 'Open at sign-up.',
    purchasePrice: 1.0,
  },
  {
    key: 'neon-tetra',
    name: 'Neon Tetra',
    image: '/akvaryum/neontera.jpg',
    imageAlt: 'Neon Tetra fish',
    tags: ['Amazon', 'Easy'],
    levelKey: 'starter',
    levelLabel: 'Level 2',
    isUnlocked: true,
    referralRequirement: 0,
    rewardDays: 5,
    meetsBalance: true,
    meetsReferrals: true,
    unlockBalance: '30',
    bonusDays: 5,
    unlockMessage: 'Balance requirement completed.',
    purchasePrice: 2.0,
  },
  {
    key: 'lepistes',
    name: 'Troud',
    image: '/akvaryum/troud.jpg',
    imageAlt: 'Lepistes fish',
    tags: ['Freshwater', 'Mid'],
    levelKey: 'mid',
    levelLabel: 'Level 3',
    isUnlocked: false,
    referralRequirement: 1,
    rewardDays: 5,
    meetsBalance: false,
    meetsReferrals: true,
    unlockBalance: '80',
    bonusDays: 0,
    unlockMessage: 'Unlock after balance reaches the required threshold.',
    purchasePrice: 3.0,
  },
  {
    key: 'angelfish',
    name: 'Angelfish',
    image: '/akvaryum/melek-baligi.jpg',
    imageAlt: 'Angelfish',
    tags: ['Freshwater', 'Mid'],
    levelKey: 'mid',
    levelLabel: 'Level 4',
    isUnlocked: false,
    referralRequirement: 1,
    rewardDays: 5,
    meetsBalance: false,
    meetsReferrals: false,
    unlockBalance: '150',
    bonusDays: 0,
    unlockMessage: 'Referral and balance target are pending.',
    purchasePrice: 4.0,
  },
  {
    key: 'discus',
    name: 'Discus Fish',
    image: '/akvaryum/diskus.jpg',
    imageAlt: 'Discus Fish',
    tags: ['Freshwater', 'Premium'],
    levelKey: 'premium',
    levelLabel: 'Level 5',
    isUnlocked: false,
    referralRequirement: 2,
    rewardDays: 5,
    meetsBalance: false,
    meetsReferrals: false,
    unlockBalance: '250',
    bonusDays: 0,
    unlockMessage: 'Premium fish food opens after advanced targets.',
    purchasePrice: 5.0,
  },
  {
    key: 'oscar',
    name: 'Oscar Fish',
    image: '/akvaryum/oscar-baligi.webp',
    imageAlt: 'Oscar Fish',
    tags: ['Freshwater', 'Premium'],
    levelKey: 'premium',
    levelLabel: 'Level 6',
    isUnlocked: false,
    referralRequirement: 4,
    rewardDays: 5,
    meetsBalance: false,
    meetsReferrals: false,
    unlockBalance: '400',
    bonusDays: 0,
    unlockMessage: 'Highest-level card is still locked.',
    purchasePrice: 6.0,
  },
]

const mapCards = [
  {
    key: 'guppy',
    name: 'Guppy',
    tags: ['Guppy', 'Freshwater'],
    habitatImage: '/map/Guppy.png',
    country: 'Venezuela',
    habitat: 'slow-moving river',
    info: 'Usually lives in calm, planted freshwater environments.',
    foodCount: 2,
    isUnlocked: true,
    nextAvailableAt: '',
    catchImage: '/akvaryum/Guppy.webp',
    catchImageAlt: 'Guppy fish',
  },
  {
    key: 'neon-tetra',
    name: 'Neon Tetra',
    tags: ['Neon Tetra', 'Amazon'],
    habitatImage: '/map/Neon Tetra.png',
    country: 'Peru',
    habitat: 'Amazon River and tributaries',
    info: 'Lives in dark, soft waters known as blackwater.',
    foodCount: 1,
    isUnlocked: true,
    nextAvailableAt: futureIso(7800),
    catchImage: '/akvaryum/neontera.jpg',
    catchImageAlt: 'Neon Tetra fish',
  },
  {
    key: 'lepistes',
    name: 'Troud',
    tags: ['Troud', 'Freshwater'],
    habitatImage: '/map/Lepistes.png',
    country: 'Trinidad',
    habitat: 'Streams, ponds, and slow-moving waters',
    info: 'Common in warm, planted waters.',
    foodCount: 0,
    isUnlocked: true,
    nextAvailableAt: '',
    catchImage: '/akvaryum/troud.jpg',
    catchImageAlt: 'Troud fish',
  },
  {
    key: 'angelfish',
    name: 'Angelfish',
    tags: ['Angelfish', 'Freshwater'],
    habitatImage: '/map/melekbaligi.png',
    country: 'Brazil',
    habitat: 'Amazon River and flooded forests',
    info: 'Lives in planted waters with roots and cover.',
    foodCount: 0,
    isUnlocked: false,
    nextAvailableAt: '',
    catchImage: '/akvaryum/melek-baligi.jpg',
    catchImageAlt: 'Angelfish',
  },
  {
    key: 'discus',
    name: 'Discus Fish',
    tags: ['Discus Fish', 'Freshwater'],
    habitatImage: '/map/discus.png',
    country: 'Brazil',
    habitat: 'Amazon River (especially still areas)',
    info: 'Lives in very clean, warm, and soft water.',
    foodCount: 0,
    isUnlocked: false,
    nextAvailableAt: '',
    catchImage: '/akvaryum/diskus.jpg',
    catchImageAlt: 'Discus Fish',
  },
  {
    key: 'oscar',
    name: 'Oscar Fish',
    tags: ['Oscar Fish', 'Freshwater'],
    habitatImage: '/map/Oscar.png',
    country: 'Colombia',
    habitat: 'Amazon and Orinoco rivers',
    info: 'Found in still, warm, planted waters.',
    foodCount: 0,
    isUnlocked: false,
    nextAvailableAt: '',
    catchImage: '/akvaryum/oscar-baligi.webp',
    catchImageAlt: 'Oscar Fish',
  },
]

const aquariumItems = [
  {
    key: 'guppy',
    name: 'Guppy',
    image: '/akvaryum/Guppy.webp',
    imageAlt: 'Guppy fish',
    tags: ['Freshwater', 'Starter'],
    levelLabel: 'Level 1',
    totalCatches: 2,
    lastCatchAt: '2026-07-21 10:12',
    isMarketReady: true,
    marketAvailableAt: futureIso(0),
    marketUrl: '/m/pages/mycargo?fish=guppy',
  },
  {
    key: 'neon-tetra',
    name: 'Neon Tetra',
    image: '/akvaryum/neontera.jpg',
    imageAlt: 'Neon Tetra fish',
    tags: ['Amazon', 'Easy'],
    levelLabel: 'Level 2',
    totalCatches: 1,
    lastCatchAt: '2026-07-21 09:48',
    isMarketReady: false,
    marketAvailableAt: futureIso(2400),
    marketUrl: '/m/pages/mycargo?fish=neon-tetra',
  },
]

const fishMarketData = {
  tradeBalance: 186.5,
  selectedFishKey: 'guppy',
  ownedFish: [
    {
      key: 'guppy',
      name: 'Guppy',
      image: '/akvaryum/Guppy.webp',
      imageAlt: 'Guppy fish',
      tags: ['Freshwater', 'Starter'],
      marketRate: 4.0,
      isMarketReady: true,
      isMarketSold: false,
      marketAvailableAt: futureIso(0),
    },
    {
      key: 'neon-tetra',
      name: 'Neon Tetra',
      image: '/akvaryum/neontera.jpg',
      imageAlt: 'Neon Tetra fish',
      tags: ['Amazon', 'Easy'],
      marketRate: 2.7,
      isMarketReady: false,
      isMarketSold: false,
      marketAvailableAt: futureIso(2400),
    },
  ],
  feed: [
    {
      seller: 'Aqua #204',
      fish: 'Neon Tetra',
      status: 'Sold',
      note: 'Closed in a short auction round with 2.70% return.',
      createdAt: now - 15 * 1000,
    },
    {
      seller: 'Aqua #118',
      fish: 'Angelfish',
      status: 'Collecting Bids',
      note: 'The market is busy. The final bid window has opened.',
      createdAt: now - 60 * 1000,
    },
    {
      seller: 'Aqua #332',
      fish: 'Discus Fish',
      status: 'In Auction',
      note: 'Bids are accelerating for this high-level fish.',
      createdAt: now - 180 * 1000,
    },
    {
      seller: 'Aqua #519',
      fish: 'Oscar Fish',
      status: 'Collecting Bids',
      note: 'Final bids are rising in the aggressive species category.',
      createdAt: now - 420 * 1000,
    },
    {
      seller: 'Aqua #087',
      fish: 'Guppy',
      status: 'Sold',
      note: 'Completed in a fast auction window with 4.00% return.',
      createdAt: now - 540 * 1000,
    },
  ],
}

const depositData = {
  selectedMethod: 'sitransferhub',
  amount: '',
  orders: [],
}

const withdrawalData = {
  displayBalance: '186.50',
  withdrawableBalance: '120.00',
  totalFeedUsed: 5,
  requiredFeedUsage: 5,
  remainingFeedUsage: 0,
  passesFeedRule: true,
  requests: [
    { requestNo: 'AQW-20260720-001', walletAddress: 'TWsF4W8D4S8J2QW6TRC20AQUA0001', status: 'approved', amount: '40.00', createdAt: '2026-07-20 13:15' },
    { requestNo: 'AQW-20260718-003', walletAddress: '0xAquaBeb20Wallet000000001', status: 'pending', amount: '15.00', createdAt: '2026-07-18 09:30' },
  ],
}

const inviteData = {
  qrUrl: '/sample-qr.svg',
  inviteCode: 'A7K9P2Q',
  inviteUrl: 'https://aqua.example/m/pages/register/A7K9P2Q',
  referralBalance: '36.20',
  memberCount: 8,
  soldCount: 3,
  totalRewardToYou: '36.20',
  totalTeamEarnings: '604.50',
}

const teamData = {
  memberCount: 8,
  soldCount: 3,
  totalRewardToYou: '36.20',
  members: [
    { id: 204, maskedEmail: 'al***@mail.com', hasSoldFish: true, saleStatusLabel: 'Sold Fish', joinedAt: '2026-07-10', totalCatches: 4, earnedBalance: '87.50', rewardToYou: '5.25', lastSaleAt: '2026-07-20 15:40' },
    { id: 118, maskedEmail: 'ro***@mail.com', hasSoldFish: false, saleStatusLabel: 'Idle', joinedAt: '2026-07-14', totalCatches: 1, earnedBalance: '18.00', rewardToYou: '1.08', lastSaleAt: '' },
    { id: 332, maskedEmail: 'mi***@mail.com', hasSoldFish: true, saleStatusLabel: 'Sold Fish', joinedAt: '2026-07-16', totalCatches: 3, earnedBalance: '64.20', rewardToYou: '3.85', lastSaleAt: '2026-07-21 09:55' },
  ],
}

const profileData = {
  userId: 27,
  avatarLetter: 'A',
  memberLabel: 'Standard',
  displayName: 'aqua.user',
  email: 'aqua.user@mail.com',
  balance: '186.50',
  bonusBalance: '25.00',
  referralBalance: '36.20',
  totalBalance: '247.70',
  inviteCode: 'A7K9P2Q',
  teamMemberCount: 8,
  totalCatches: 3,
  withdrawableBalance: '120.00',
}

const newsData = {
  featured: {
    tag: 'Market Pulse',
    title: 'Busy Session in the Fish Market Today: Guppy and Neon Tetra Lead the Way',
    summary: 'Fast sales stood out in the morning session. Buyer interest increased in small and mid-level fish, while the quickest-closing auctions of the day were seen in Guppy and Neon Tetra.',
    time: 'Today 10:45',
  },
  items: [
    { tag: 'Price Increase', title: 'Strong rise in Guppy pricing', summary: 'Guppy return bands strengthened due to rising demand in short auctions.', time: 'Today 09:20' },
    { tag: 'Demand', title: 'Heavy interest in Neon Tetra sessions', summary: 'Buyer count increased in the schooling fish category. There is a clear rise in intraday bid volume.', time: 'Today 09:55' },
    { tag: 'Medium Level', title: 'Lepistes and Angelfish are moving steadily', summary: 'Sales in the medium-level category remain stable. Closing times are faster than yesterday.', time: 'Today 11:10' },
    { tag: 'High Demand', title: 'Premium buyer flow continues on the Discus side', summary: 'Bids remain more selective in Discus auctions, but per-unit returns stay high.', time: 'Today 11:40' },
    { tag: 'Live Session', title: 'Last-minute bids accelerated in the Oscar session', summary: 'Final bid windows look more competitive in high-volume trades.', time: 'Today 12:05' },
    { tag: 'End of Day', title: 'Strong sales are expected in the fish market', summary: 'As the number of active cards rises toward the evening session, total market volume is expected to strengthen.', time: 'Today 12:30' },
  ],
}

export function getDashboardData() {
  return clone({ menuItems: dashboardMenuItems, fishList: dashboardFishList })
}

export function getFishFoodData() {
  return clone({ balance: 186.5, cards: fishFoodCards })
}

export function getMapData() {
  return clone({ cards: mapCards })
}

export function getAquariumData() {
  return clone({ items: aquariumItems })
}

export function getFishMarketData() {
  return clone(fishMarketData)
}

export function getDepositData() {
  return clone(depositData)
}

export function getWithdrawalData() {
  return clone(withdrawalData)
}

export function getInviteData() {
  return clone(inviteData)
}

export function getTeamData() {
  return clone(teamData)
}

export function getProfileData() {
  return clone(profileData)
}

export function getNewsData() {
  return clone(newsData)
}
