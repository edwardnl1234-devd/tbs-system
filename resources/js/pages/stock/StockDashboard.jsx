import React, { useState, useEffect } from 'react';
import { stockApi } from '../../services/api';
import { Card, CardBody, CardHeader } from '../../components/ui/Card';
import Badge from '../../components/ui/Badge';
import Table, { Thead, Tbody, Tr, Th, Td } from '../../components/ui/Table';
import { PageLoading } from '../../components/ui/Loading';
import Alert from '../../components/ui/Alert';
import { formatNumber, formatDate, getStatusColor, getStatusLabel } from '../../utils/helpers';

export default function StockDashboard() {
    const [activeTab, setActiveTab] = useState('cpo');
    const [stockData, setStockData] = useState([]);
    const [summary, setSummary] = useState(null);
    const [loading, setLoading] = useState(true);
    const [alert, setAlert] = useState(null);

    useEffect(() => {
        fetchData();
    }, [activeTab]);

    const fetchData = async () => {
        setLoading(true);
        try {
            let dataRes, summaryRes;
            
            switch (activeTab) {
                case 'cpo':
                    [dataRes, summaryRes] = await Promise.all([
                        stockApi.getCpo({ per_page: 50 }),
                        stockApi.getCpoSummary(),
                    ]);
                    break;
                case 'kernel':
                    [dataRes, summaryRes] = await Promise.all([
                        stockApi.getKernel({ per_page: 50 }),
                        stockApi.getKernelSummary(),
                    ]);
                    break;
                case 'shell':
                    [dataRes, summaryRes] = await Promise.all([
                        stockApi.getShell({ per_page: 50 }),
                        stockApi.getShellSummary(),
                    ]);
                    break;
            }

            setStockData(dataRes.data.data || []);
            setSummary(summaryRes.data.data);
        } catch (error) {
            setAlert({ type: 'error', message: 'Gagal memuat data stok' });
        } finally {
            setLoading(false);
        }
    };

    const tabs = [
        { id: 'cpo', label: 'CPO', color: 'yellow' },
        { id: 'kernel', label: 'Kernel', color: 'orange' },
        { id: 'shell', label: 'Shell', color: 'gray' },
    ];

    return (
        <div className="space-y-6">
            {alert && (
                <Alert type={alert.type} message={alert.message} />
            )}

            {/* Header */}
            <div>
                <h1 className="text-2xl font-bold text-gray-900">Stok Produk</h1>
                <p className="text-gray-500">Kelola stok CPO, Kernel, dan Shell</p>
            </div>

            {/* Tabs */}
            <div className="border-b border-gray-200">
                <nav className="-mb-px flex space-x-8">
                    {tabs.map((tab) => (
                        <button
                            key={tab.id}
                            onClick={() => setActiveTab(tab.id)}
                            className={`
                                whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm
                                ${activeTab === tab.id
                                    ? 'border-green-500 text-green-600'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                }
                            `}
                        >
                            {tab.label}
                        </button>
                    ))}
                </nav>
            </div>

            {/* Summary Cards */}
            {summary && (
                <div className="space-y-4">
                    {/* Total Available */}
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <Card>
                            <CardBody className="text-center">
                                <div className="text-3xl font-bold text-green-600">
                                    {formatNumber(summary.total_available || summary.available || 0)}
                                </div>
                                <div className="text-sm text-gray-500">Total Tersedia (kg)</div>
                            </CardBody>
                        </Card>
                        <Card>
                            <CardBody className="text-center">
                                <div className="text-3xl font-bold text-yellow-600">
                                    {formatNumber(summary.reserved || 0)}
                                </div>
                                <div className="text-sm text-gray-500">Direservasi (kg)</div>
                            </CardBody>
                        </Card>
                        <Card>
                            <CardBody className="text-center">
                                <div className="text-3xl font-bold text-blue-600">
                                    {formatNumber(summary.in_transit || summary.processing || summary.total_transit || 0)}
                                </div>
                                <div className="text-sm text-gray-500">Dalam Proses (kg)</div>
                            </CardBody>
                        </Card>
                        <Card>
                            <CardBody className="text-center">
                                <div className="text-3xl font-bold text-gray-600">
                                    {formatNumber(summary.sold || summary.total_out || summary.total_sold || 0)}
                                </div>
                                <div className="text-sm text-gray-500">Terjual (kg)</div>
                            </CardBody>
                        </Card>
                    </div>
                    
                    {/* Sumber Stok: Produksi vs Pembelian */}
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <Card>
                            <CardBody>
                                <div className="flex items-center justify-between">
                                    <div>
                                        <div className="text-sm text-gray-500">Dari Produksi</div>
                                        <div className="text-2xl font-bold text-blue-600">
                                            {formatNumber(summary.from_production || 0)} kg
                                        </div>
                                    </div>
                                    <div className="h-12 w-12 bg-blue-100 rounded-full flex items-center justify-center">
                                        <span className="text-blue-600 text-xl">🏭</span>
                                    </div>
                                </div>
                            </CardBody>
                        </Card>
                        <Card>
                            <CardBody>
                                <div className="flex items-center justify-between">
                                    <div>
                                        <div className="text-sm text-gray-500">Dari Pembelian</div>
                                        <div className="text-2xl font-bold text-purple-600">
                                            {formatNumber(summary.from_purchase || 0)} kg
                                        </div>
                                    </div>
                                    <div className="h-12 w-12 bg-purple-100 rounded-full flex items-center justify-center">
                                        <span className="text-purple-600 text-xl">🛒</span>
                                    </div>
                                </div>
                            </CardBody>
                        </Card>
                    </div>
                </div>
            )}

            {/* Stock Table */}
            <Card>
                <CardHeader>
                    <h3 className="text-lg font-medium text-gray-900">
                        Riwayat Stok {tabs.find(t => t.id === activeTab)?.label}
                    </h3>
                </CardHeader>
                {loading ? (
                    <PageLoading />
                ) : (
                    <Table>
                        <Thead>
                            <Tr>
                                <Th>Tanggal</Th>
                                <Th>Referensi</Th>
                                <Th>Tipe</Th>
                                <Th>Jumlah (kg)</Th>
                                <Th>Status</Th>
                                <Th>Keterangan</Th>
                            </Tr>
                        </Thead>
                        <Tbody>
                            {stockData.length === 0 ? (
                                <Tr>
                                    <Td colSpan={6} className="text-center text-gray-500 py-8">
                                        Tidak ada data stok
                                    </Td>
                                </Tr>
                            ) : (
                                stockData.map((stock) => (
                                    <Tr key={stock.id}>
                                        <Td>{formatDate(stock.created_at || stock.date)}</Td>
                                        <Td className="font-mono text-sm">
                                            {stock.reference_number || stock.batch_number || '-'}
                                        </Td>
                                        <Td>
                                            <Badge color={stock.movement_type === 'in' ? 'green' : 'red'}>
                                                {stock.movement_type === 'in' ? 'Masuk' : 'Keluar'}
                                            </Badge>
                                        </Td>
                                        <Td className={`font-medium ${stock.movement_type === 'in' ? 'text-green-600' : 'text-red-600'}`}>
                                            {stock.movement_type === 'in' ? '+' : '-'}{formatNumber(stock.quantity)}
                                        </Td>
                                        <Td>
                                            <Badge color={getStatusColor(stock.status)}>
                                                {getStatusLabel(stock.status)}
                                            </Badge>
                                        </Td>
                                        <Td className="text-gray-500 max-w-xs truncate">
                                            {stock.notes || stock.description || '-'}
                                        </Td>
                                    </Tr>
                                ))
                            )}
                        </Tbody>
                    </Table>
                )}
            </Card>

            {/* Tank/Storage Info for CPO */}
            {activeTab === 'cpo' && summary?.tanks && (
                <Card>
                    <CardHeader>
                        <h3 className="text-lg font-medium text-gray-900">Status Tangki</h3>
                    </CardHeader>
                    <CardBody>
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                            {summary.tanks.map((tank, index) => (
                                <div key={index} className="border rounded-lg p-4">
                                    <div className="flex justify-between items-center mb-2">
                                        <span className="font-medium">Tangki {tank.name || index + 1}</span>
                                        <span className="text-sm text-gray-500">
                                            {tank.capacity ? `${((tank.current / tank.capacity) * 100).toFixed(1)}%` : ''}
                                        </span>
                                    </div>
                                    <div className="w-full bg-gray-200 rounded-full h-3 mb-2">
                                        <div
                                            className="bg-yellow-500 h-3 rounded-full"
                                            style={{ width: `${tank.capacity ? (tank.current / tank.capacity) * 100 : 0}%` }}
                                        ></div>
                                    </div>
                                    <div className="text-sm text-gray-600">
                                        {formatNumber(tank.current || 0)} / {formatNumber(tank.capacity || 0)} kg
                                    </div>
                                </div>
                            ))}
                        </div>
                    </CardBody>
                </Card>
            )}
        </div>
    );
}
