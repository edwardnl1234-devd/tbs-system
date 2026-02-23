import React, { Fragment, useState } from 'react';
import { Outlet, Link, useLocation, useNavigate } from 'react-router-dom';
import { Dialog, Transition, Menu } from '@headlessui/react';
import {
    Bars3Icon,
    XMarkIcon,
    HomeIcon,
    QueueListIcon,
    ScaleIcon,
    CubeIcon,
    CurrencyDollarIcon,
    TruckIcon,
    UserGroupIcon,
    BuildingStorefrontIcon,
    TagIcon,
    UsersIcon,
    ChartBarIcon,
    ArrowRightOnRectangleIcon,
    Cog6ToothIcon,
    ChevronDownIcon,
} from '@heroicons/react/24/outline';
import { useAuth } from '../contexts/AuthContext';

const navigation = [
    { name: 'Dashboard', href: '/', icon: HomeIcon },
    { name: 'Antrian', href: '/queues', icon: QueueListIcon },
    { name: 'Timbangan', href: '/weighings', icon: ScaleIcon },
    { name: 'Produksi', href: '/productions', icon: CubeIcon },
    { name: 'Stok', href: '/stock', icon: CubeIcon },
    { name: 'Pembelian Stok', href: '/stock/purchases', icon: BuildingStorefrontIcon },
    { name: 'Penjualan', href: '/sales', icon: CurrencyDollarIcon },
];

const masterData = [
    { name: 'Supplier', href: '/suppliers', icon: BuildingStorefrontIcon },
    { name: 'Customer', href: '/customers', icon: UserGroupIcon },
    { name: 'Truk', href: '/trucks', icon: TruckIcon },
    { name: 'Harga TBS', href: '/tbs-prices', icon: TagIcon },
];

// Management menu - will be filtered based on role
const managementItems = [
    { name: 'Pengguna', href: '/users', icon: UsersIcon, roles: ['admin'] },
    { name: 'Laporan', href: '/reports', icon: ChartBarIcon, roles: ['admin', 'manager', 'accounting'] },
];

function classNames(...classes) {
    return classes.filter(Boolean).join(' ');
}

export default function Layout() {
    const [sidebarOpen, setSidebarOpen] = useState(false);
    const location = useLocation();
    const navigate = useNavigate();
    const { user, logout } = useAuth();

    const handleLogout = async () => {
        await logout();
        navigate('/login');
    };

    const NavSection = ({ title, items }) => (
        <div className="mb-6">
            {title && (
                <h3 className="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                    {title}
                </h3>
            )}
            <nav className="space-y-1">
                {items.map((item) => {
                    const isActive = location.pathname === item.href;
                    return (
                        <Link
                            key={item.name}
                            to={item.href}
                            className={classNames(
                                isActive
                                    ? 'bg-green-100 text-green-700 border-r-4 border-green-700'
                                    : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900',
                                'group flex items-center px-3 py-2 text-sm font-medium rounded-l-md transition-colors'
                            )}
                        >
                            <item.icon
                                className={classNames(
                                    isActive ? 'text-green-700' : 'text-gray-400 group-hover:text-gray-500',
                                    'mr-3 h-5 w-5 flex-shrink-0'
                                )}
                            />
                            {item.name}
                        </Link>
                    );
                })}
            </nav>
        </div>
    );

    const SidebarContent = () => {
        // Filter management items based on user role
        const management = managementItems.filter(item => 
            !item.roles || item.roles.includes(user?.role)
        );

        return (
            <div className="flex flex-col h-full">
                {/* Logo */}
                <div className="flex items-center h-16 px-4 bg-green-700">
                    <div className="flex items-center">
                        <CubeIcon className="h-8 w-8 text-white" />
                        <span className="ml-2 text-xl font-bold text-white">TBS System</span>
                    </div>
                </div>

                {/* Navigation */}
                <div className="flex-1 overflow-y-auto py-4 px-2">
                    <NavSection items={navigation} />
                    <NavSection title="Master Data" items={masterData} />
                    {management.length > 0 && (
                        <NavSection title="Management" items={management} />
                    )}
                </div>

                {/* User Info */}
                <div className="border-t border-gray-200 p-4">
                    <div className="flex items-center">
                        <div className="h-10 w-10 rounded-full bg-green-600 flex items-center justify-center">
                            <span className="text-white font-medium">
                                {user?.name?.charAt(0)?.toUpperCase() || 'U'}
                            </span>
                        </div>
                        <div className="ml-3">
                            <p className="text-sm font-medium text-gray-700">{user?.name}</p>
                            <p className="text-xs text-gray-500 capitalize">{user?.role}</p>
                        </div>
                    </div>
                </div>
            </div>
        );
    };

    return (
        <div className="min-h-screen bg-gray-50">
            {/* Mobile sidebar */}
            <Transition.Root show={sidebarOpen} as={Fragment}>
                <Dialog as="div" className="relative z-50 lg:hidden" onClose={setSidebarOpen}>
                    <Transition.Child
                        as={Fragment}
                        enter="transition-opacity ease-linear duration-300"
                        enterFrom="opacity-0"
                        enterTo="opacity-100"
                        leave="transition-opacity ease-linear duration-300"
                        leaveFrom="opacity-100"
                        leaveTo="opacity-0"
                    >
                        <div className="fixed inset-0 bg-gray-900/80" />
                    </Transition.Child>

                    <div className="fixed inset-0 flex">
                        <Transition.Child
                            as={Fragment}
                            enter="transition ease-in-out duration-300 transform"
                            enterFrom="-translate-x-full"
                            enterTo="translate-x-0"
                            leave="transition ease-in-out duration-300 transform"
                            leaveFrom="translate-x-0"
                            leaveTo="-translate-x-full"
                        >
                            <Dialog.Panel className="relative mr-16 flex w-full max-w-xs flex-1">
                                <Transition.Child
                                    as={Fragment}
                                    enter="ease-in-out duration-300"
                                    enterFrom="opacity-0"
                                    enterTo="opacity-100"
                                    leave="ease-in-out duration-300"
                                    leaveFrom="opacity-100"
                                    leaveTo="opacity-0"
                                >
                                    <div className="absolute left-full top-0 flex w-16 justify-center pt-5">
                                        <button
                                            type="button"
                                            className="-m-2.5 p-2.5"
                                            onClick={() => setSidebarOpen(false)}
                                        >
                                            <XMarkIcon className="h-6 w-6 text-white" />
                                        </button>
                                    </div>
                                </Transition.Child>
                                <div className="flex grow flex-col overflow-y-auto bg-white">
                                    <SidebarContent />
                                </div>
                            </Dialog.Panel>
                        </Transition.Child>
                    </div>
                </Dialog>
            </Transition.Root>

            {/* Desktop sidebar */}
            <div className="hidden lg:fixed lg:inset-y-0 lg:z-50 lg:flex lg:w-64 lg:flex-col">
                <div className="flex grow flex-col overflow-y-auto bg-white border-r border-gray-200">
                    <SidebarContent />
                </div>
            </div>

            {/* Main content */}
            <div className="lg:pl-64">
                {/* Top bar */}
                <div className="sticky top-0 z-40 flex h-16 shrink-0 items-center gap-x-4 border-b border-gray-200 bg-white px-4 shadow-sm sm:gap-x-6 sm:px-6 lg:px-8">
                    <button
                        type="button"
                        className="-m-2.5 p-2.5 text-gray-700 lg:hidden"
                        onClick={() => setSidebarOpen(true)}
                    >
                        <Bars3Icon className="h-6 w-6" />
                    </button>

                    {/* Separator */}
                    <div className="h-6 w-px bg-gray-200 lg:hidden" />

                    <div className="flex flex-1 gap-x-4 self-stretch lg:gap-x-6">
                        <div className="flex flex-1 items-center">
                            <h1 className="text-lg font-semibold text-gray-900">
                                {navigation.find(n => n.href === location.pathname)?.name ||
                                 masterData.find(n => n.href === location.pathname)?.name ||
                                 managementItems.find(n => n.href === location.pathname)?.name ||
                                 'TBS System'}
                            </h1>
                        </div>

                        <div className="flex items-center gap-x-4 lg:gap-x-6">
                            {/* Profile dropdown */}
                            <Menu as="div" className="relative">
                                <Menu.Button className="flex items-center p-1.5 hover:bg-gray-100 rounded-lg">
                                    <div className="h-8 w-8 rounded-full bg-green-600 flex items-center justify-center">
                                        <span className="text-white text-sm font-medium">
                                            {user?.name?.charAt(0)?.toUpperCase() || 'U'}
                                        </span>
                                    </div>
                                    <span className="hidden lg:flex lg:items-center ml-2">
                                        <span className="text-sm font-medium text-gray-700">
                                            {user?.name}
                                        </span>
                                        <ChevronDownIcon className="ml-2 h-4 w-4 text-gray-400" />
                                    </span>
                                </Menu.Button>
                                <Transition
                                    as={Fragment}
                                    enter="transition ease-out duration-100"
                                    enterFrom="transform opacity-0 scale-95"
                                    enterTo="transform opacity-100 scale-100"
                                    leave="transition ease-in duration-75"
                                    leaveFrom="transform opacity-100 scale-100"
                                    leaveTo="transform opacity-0 scale-95"
                                >
                                    <Menu.Items className="absolute right-0 z-10 mt-2.5 w-48 origin-top-right rounded-md bg-white py-2 shadow-lg ring-1 ring-gray-900/5 focus:outline-none">
                                        <Menu.Item>
                                            {({ active }) => (
                                                <Link
                                                    to="/profile"
                                                    className={classNames(
                                                        active ? 'bg-gray-50' : '',
                                                        'flex items-center px-4 py-2 text-sm text-gray-700'
                                                    )}
                                                >
                                                    <Cog6ToothIcon className="mr-3 h-5 w-5 text-gray-400" />
                                                    Pengaturan
                                                </Link>
                                            )}
                                        </Menu.Item>
                                        <Menu.Item>
                                            {({ active }) => (
                                                <button
                                                    onClick={handleLogout}
                                                    className={classNames(
                                                        active ? 'bg-gray-50' : '',
                                                        'flex items-center w-full px-4 py-2 text-sm text-gray-700'
                                                    )}
                                                >
                                                    <ArrowRightOnRectangleIcon className="mr-3 h-5 w-5 text-gray-400" />
                                                    Keluar
                                                </button>
                                            )}
                                        </Menu.Item>
                                    </Menu.Items>
                                </Transition>
                            </Menu>
                        </div>
                    </div>
                </div>

                {/* Page content */}
                <main className="py-6">
                    <div className="px-4 sm:px-6 lg:px-8">
                        <Outlet />
                    </div>
                </main>
            </div>
        </div>
    );
}
