import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import {
    QueueListIcon,
    ScaleIcon,
    CubeIcon,
    CurrencyDollarIcon,
    ArrowTrendingUpIcon,
    ArrowTrendingDownIcon,
    ClockIcon,
    TruckIcon,
    ExclamationTriangleIcon,
} from '@heroicons/react/24/outline';
import { 
    BarChart, 
    Bar, 
    XAxis, 
    YAxis, 
    CartesianGrid, 
    Tooltip, 
    ResponsiveContainer,
    PieChart,
    Pie,
    Cell,
    LineChart,
    Line,
} from 'recharts';
import { dashboardApi } from '../services/api';
import { Card, CardBody, CardHeader } from '../components/ui/Card';
import Badge from '../components/ui/Badge';
import { PageLoading } from '../components/ui/Loading';
import { formatNumber, formatCurrency } from '../utils/helpers';

const COLORS = ['#16a34a', '#22c55e', '#4ade80', '#86efac'];

export default function Dashboard() {
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {
        fetchDashboardData();
        const interval = setInterval(fetchDashboardData, 30000); // Refresh every 30s
        return () => clearInterval(interval);
    }, []);

    const fetchDashboardData = async () => {
        try {
            setError(null);
            const response = await dashboardApi.getAll();
            if (response.data && response.data.data) {
                setData(response.data.data);
            } else {
                setData({});
            }
        } catch (error) {
            console.error('Failed to fetch dashboard data:', error);
            setError('Gagal memuat data dashboard. Silakan refresh halaman.');
            // Don't clear existing data on error
        } finally {
            setLoading(false);
        }
    };

    if (loading) return <PageLoading />;

    // Show error state but don't crash
    if (error && !data) {
        return (
            <div className="flex items-center justify-center h-64">
                <div className="text-center">
                    <ExclamationTriangleIcon className="h-12 w-12 text-yellow-500 mx-auto mb-4" />
                    <p className="text-gray-600">{error}</p>
                    <button 
                        onClick={fetchDashboardData}
                        className="mt-4 px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700"
                    >
                        Coba Lagi
                    </button>
                </div>
            </div>
        );
    }

    // Safe access with defaults
    const queueData = data?.queue || {};
    const weighingData = data?.weighing || {};
    const productionData = data?.production || {};
    const stockData = data?.stock || {};
    const salesData = data?.sales || {};

    const productionChartData = [
        { name: 'CPO', value: productionData.total_cpo || 0 },
        { name: 'Kernel', value: productionData.total_kernel || 0 },
        { name: 'Shell', value: productionData.total_shell || 0 },
    ];

    return (
        <div className="space-y-6">
            {/* Stats Overview */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <StatCard
                    title="Antrian Hari Ini"
                    value={queueData.total_today || 0}
                    subtext={`${queueData.waiting || 0} menunggu, ${queueData.processing || 0} proses`}
                    icon={QueueListIcon}
                    color="blue"
                    link="/queues"
                />
                <StatCard
                    title="Total Timbangan"
                    value={formatNumber(productionData.total_input || 0)}
                    subtext="kg TBS hari ini"
                    icon={ScaleIcon}
                    color="green"
                    link="/weighings"
                />
                <StatCard
                    title="Produksi CPO"
                    value={formatNumber(productionData.total_cpo || 0)}
                    subtext={`OER: ${parseFloat(productionData.oer || 0).toFixed(2)}%`}
                    icon={CubeIcon}
                    color="yellow"
                    link="/productions"
                />
                <StatCard
                    title="Penjualan"
                    value={formatCurrency(salesData.total_revenue || 0)}
                    subtext={`${salesData.total_transactions || 0} transaksi`}
                    icon={CurrencyDollarIcon}
                    color="purple"
                    link="/sales"
                />
            </div>

            {/* Charts Row */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {/* Queue Status */}
                <Card>
                    <CardHeader>
                        <h3 className="text-lg font-medium text-gray-900">Status Antrian</h3>
                    </CardHeader>
                    <CardBody>
                        <div className="grid grid-cols-4 gap-4 text-center">
                            <div className="p-4 bg-yellow-50 rounded-lg">
                                <p className="text-3xl font-bold text-yellow-600">
                                    {queueData.waiting || 0}
                                </p>
                                <p className="text-sm text-yellow-700">Menunggu</p>
                            </div>
                            <div className="p-4 bg-blue-50 rounded-lg">
                                <p className="text-3xl font-bold text-blue-600">
                                    {queueData.processing || 0}
                                </p>
                                <p className="text-sm text-blue-700">Diproses</p>
                            </div>
                            <div className="p-4 bg-green-50 rounded-lg">
                                <p className="text-3xl font-bold text-green-600">
                                    {queueData.completed || 0}
                                </p>
                                <p className="text-sm text-green-700">Selesai</p>
                            </div>
                            <div className="p-4 bg-red-50 rounded-lg">
                                <p className="text-3xl font-bold text-red-600">
                                    {queueData.cancelled || 0}
                                </p>
                                <p className="text-sm text-red-700">Batal</p>
                            </div>
                        </div>
                        <div className="mt-4 flex items-center justify-center text-gray-500">
                            <ClockIcon className="h-5 w-5 mr-2" />
                            <span>Rata-rata waktu tunggu: {Math.round(queueData.avg_wait_time || 0)} menit</span>
                        </div>
                    </CardBody>
                </Card>

                {/* Production Output */}
                <Card>
                    <CardHeader>
                        <h3 className="text-lg font-medium text-gray-900">Output Produksi Hari Ini</h3>
                    </CardHeader>
                    <CardBody>
                        <div className="h-64">
                            <ResponsiveContainer width="100%" height="100%">
                                <PieChart>
                                    <Pie
                                        data={productionChartData}
                                        cx="50%"
                                        cy="50%"
                                        innerRadius={60}
                                        outerRadius={80}
                                        paddingAngle={5}
                                        dataKey="value"
                                        label={({ name, value }) => `${name}: ${formatNumber(value)} kg`}
                                    >
                                        {productionChartData.map((entry, index) => (
                                            <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                                        ))}
                                    </Pie>
                                    <Tooltip formatter={(value) => formatNumber(value) + ' kg'} />
                                </PieChart>
                            </ResponsiveContainer>
                        </div>
                    </CardBody>
                </Card>
            </div>

            {/* Stock Summary */}
            <Card>
                <CardHeader>
                    <h3 className="text-lg font-medium text-gray-900">Ringkasan Stok</h3>
                </CardHeader>
                <CardBody>
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <StockCard
                            title="Stok TBS"
                            available={stockData.tbs?.total || 0}
                            processing={stockData.tbs?.processing || 0}
                            unit="kg"
                            color="green"
                        />
                        <StockCard
                            title="Stok CPO"
                            available={stockData.cpo?.available || 0}
                            reserved={stockData.cpo?.reserved || 0}
                            unit="kg"
                            color="yellow"
                        />
                        <StockCard
                            title="Stok Kernel"
                            available={stockData.kernel?.available || 0}
                            sold={stockData.kernel?.sold || 0}
                            unit="kg"
                            color="orange"
                        />
                        <StockCard
                            title="Stok Shell"
                            available={stockData.shell?.available || 0}
                            sold={stockData.shell?.sold || 0}
                            unit="kg"
                            color="gray"
                        />
                    </div>
                </CardBody>
            </Card>

            {/* Extraction Rates */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <Card>
                    <CardHeader>
                        <h3 className="text-lg font-medium text-gray-900">Tingkat Ekstraksi</h3>
                    </CardHeader>
                    <CardBody>
                        <div className="space-y-4">
                            <ExtractionRate
                                label="Oil Extraction Rate (OER)"
                                value={productionData.oer || 0}
                                target={22}
                                color="green"
                            />
                            <ExtractionRate
                                label="Kernel Extraction Rate (KER)"
                                value={productionData.ker || 0}
                                target={5}
                                color="yellow"
                            />
                        </div>
                    </CardBody>
                </Card>

                <Card>
                    <CardHeader>
                        <h3 className="text-lg font-medium text-gray-900">Statistik Cepat</h3>
                    </CardHeader>
                    <CardBody>
                        <div className="grid grid-cols-2 gap-4">
                            <div className="text-center p-4 bg-gray-50 rounded-lg">
                                <TruckIcon className="h-8 w-8 mx-auto text-gray-400 mb-2" />
                                <p className="text-2xl font-bold text-gray-900">
                                    {productionData.batches || 0}
                                </p>
                                <p className="text-sm text-gray-500">Batch Produksi</p>
                            </div>
                            <div className="text-center p-4 bg-gray-50 rounded-lg">
                                <ScaleIcon className="h-8 w-8 mx-auto text-gray-400 mb-2" />
                                <p className="text-2xl font-bold text-gray-900">
                                    {formatNumber(productionData.total_input || 0)}
                                </p>
                                <p className="text-sm text-gray-500">Total Input (kg)</p>
                            </div>
                        </div>
                    </CardBody>
                </Card>
            </div>
        </div>
    );
}

function StatCard({ title, value, subtext, icon: Icon, color, link }) {
    const colors = {
        blue: 'bg-blue-500',
        green: 'bg-green-500',
        yellow: 'bg-yellow-500',
        purple: 'bg-purple-500',
    };

    return (
        <Link to={link}>
            <Card className="hover:shadow-lg transition-shadow cursor-pointer">
                <CardBody>
                    <div className="flex items-center">
                        <div className={`p-3 rounded-lg ${colors[color]}`}>
                            <Icon className="h-6 w-6 text-white" />
                        </div>
                        <div className="ml-4">
                            <p className="text-sm font-medium text-gray-500">{title}</p>
                            <p className="text-2xl font-semibold text-gray-900">{value}</p>
                            <p className="text-sm text-gray-500">{subtext}</p>
                        </div>
                    </div>
                </CardBody>
            </Card>
        </Link>
    );
}

function StockCard({ title, available, processing, reserved, sold, unit, color }) {
    return (
        <div className="p-4 border rounded-lg">
            <h4 className="font-medium text-gray-900 mb-3">{title}</h4>
            <p className="text-2xl font-bold text-gray-900">
                {formatNumber(available)} <span className="text-sm font-normal">{unit}</span>
            </p>
            <div className="mt-2 space-y-1">
                {processing !== undefined && (
                    <div className="flex justify-between text-sm">
                        <span className="text-gray-500">Diproses:</span>
                        <span className="text-blue-600">{formatNumber(processing)} {unit}</span>
                    </div>
                )}
                {reserved !== undefined && (
                    <div className="flex justify-between text-sm">
                        <span className="text-gray-500">Direservasi:</span>
                        <span className="text-yellow-600">{formatNumber(reserved)} {unit}</span>
                    </div>
                )}
                {sold !== undefined && (
                    <div className="flex justify-between text-sm">
                        <span className="text-gray-500">Terjual:</span>
                        <span className="text-green-600">{formatNumber(sold)} {unit}</span>
                    </div>
                )}
            </div>
        </div>
    );
}

function ExtractionRate({ label, value, target, color }) {
    const percentage = Math.min((value / target) * 100, 100);
    const isGood = value >= target * 0.9;

    return (
        <div>
            <div className="flex justify-between mb-1">
                <span className="text-sm font-medium text-gray-700">{label}</span>
                <span className={`text-sm font-medium ${isGood ? 'text-green-600' : 'text-yellow-600'}`}>
                    {parseFloat(value || 0).toFixed(2)}% / {target}%
                </span>
            </div>
            <div className="w-full bg-gray-200 rounded-full h-2.5">
                <div
                    className={`h-2.5 rounded-full ${isGood ? 'bg-green-500' : 'bg-yellow-500'}`}
                    style={{ width: `${percentage}%` }}
                ></div>
            </div>
        </div>
    );
}
