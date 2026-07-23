import { createRouter, createWebHistory } from 'vue-router'
import Login from '../views/auth/Login.vue'
import Register from '../views/auth/Register.vue'
import Dashboard from '../views/dashboard/Dashboard.vue'
import Map from '../views/fish/Map.vue'
import Aquarium from '../views/fish/Aquarium.vue'
import FishMarket from '../views/fish/FishMarket.vue'
import Deposit from '../views/wallet/Deposit.vue'
import Withdrawal from '../views/wallet/Withdrawal.vue'
import Invite from '../views/referral/Invite.vue'
import Team from '../views/referral/Team.vue'
import Profile from '../views/account/Profile.vue'
import CommissionHistory from '../views/account/CommissionHistory.vue'
import OtherHistory from '../views/account/OtherHistory.vue'
import InterestHistory from '../views/account/InterestHistory.vue'
import News from '../views/info/News.vue'
import Security from '../views/info/Security.vue'
import Company from '../views/info/Company.vue'
import IndexBank from '../views/bank/IndexBank.vue'
import AddBank from '../views/bank/AddBank.vue'

const publicRouteNames = new Set(['login', 'register'])
const defaultAuthenticatedRoute = '/m/pages/dashboard'

function hasAccessToken() {
  return Boolean(localStorage.getItem('auth_access_token'))
}

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/',
      redirect: () => (hasAccessToken() ? defaultAuthenticatedRoute : '/m/pages/sign'),
    },
    {
      path: '/m/pages/sign',
      name: 'login',
      component: Login,
    },
    {
      path: '/m/pages/register/:referralCode?',
      name: 'register',
      component: Register,
    },
    {
      path: '/m/pages/dashboard',
      name: 'dashboard',
      component: Dashboard,
    },
    {
      path: '/m/pages/map',
      name: 'map',
      component: Map,
    },
    {
      path: '/m/pages/cargo',
      name: 'aquarium',
      component: Aquarium,
    },
    {
      path: '/m/pages/mycargo',
      name: 'fish-market',
      component: FishMarket,
    },
    {
      path: '/m/pages/addbalance',
      name: 'deposit',
      component: Deposit,
    },
    {
      path: '/m/pages/settle',
      name: 'withdrawal',
      component: Withdrawal,
    },
    {
      path: '/m/pages/invite',
      name: 'invite',
      component: Invite,
    },
    {
      path: '/m/pages/myteam',
      name: 'team',
      component: Team,
    },
    {
      path: '/m/pages/myteam/all',
      name: 'team-all',
      component: Team,
    },
    {
      path: '/m/pages/profile',
      name: 'profile',
      component: Profile,
    },
    {
      path: '/m/pages/history/income',
      name: 'interest-history',
      component: InterestHistory,
    },
    {
      path: '/m/pages/history/commision',
      name: 'commission-history',
      component: CommissionHistory,
    },
    {
      path: '/m/pages/history/others',
      name: 'other-history',
      component: OtherHistory,
    },
    {
      path: '/m/pages/news',
      name: 'news',
      component: News,
    },
    {
      path: '/m/pages/policy',
      name: 'security',
      component: Security,
    },
    {
      path: '/m/pages/company',
      name: 'company',
      component: Company,
    },
    {
      path: '/m/pages/wallet',
      name: 'banks',
      component: IndexBank,
    },
    {
      path: '/m/pages/wallet/add',
      name: 'banks-add',
      component: AddBank,
    },
  ],
})

router.beforeEach((to) => {
  const isAuthenticated = hasAccessToken()

  if (publicRouteNames.has(to.name)) {
    if (isAuthenticated) {
      return { path: defaultAuthenticatedRoute }
    }

    return true
  }

  if (!isAuthenticated) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  return true
})

export default router
