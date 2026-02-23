import React, { useState, useEffect } from 'react';
import { 
    DocumentChartBarIcon,
    ArrowDownTrayIcon,
    CalendarIcon,
} from '@heroicons/react/24/outline';
import { reportApi } from '../../services/api';
import { Card, CardBody, CardHeader } from '../../components/ui/Card';
import Button from '../../components/ui/Button';
import Input from '../../components/ui/Input';
import Select from '../../components/ui/Select';
import Table, { Thead, Tbody, Tr, Th, Td } from '../../components/ui/Table';
import { PageLoading } from '../../components/ui/Loading';
import Alert from '../../components/ui/Alert';
import { formatDate, formatNumber, formatCurrency } from '../../utils/helpers';
import {
    LineChart,
    Line,
    BarChart,
    Bar,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    Legend,
    ResponsiveContainer,
} from 'recharts';

const reportTypes = [
    { value: 'daily', label: 'Laporan Harian' },
    { value: 'weekly', label: 'Laporan Mingguan' },
    { value: 'monthly', label: 'Laporan Bulanan' },
    { value: 'production', label: 'Laporan Produksi' },
    { value: 'stock', label: 'Laporan Pergerakan Stok' },
    { value: 'margin', label: 'Laporan Margin' },
];

export default function Reports() {
    const [reportType, setReportType] = useState('daily');
    const [dateFrom, setDateFrom] = useState(new Date().toISOString().split('T')[0]);
    const [dateTo, setDateTo] = useState(new Date().toISOString().split('T')[0]);
    const [reportData, setReportData] = useState(null);
    const [loading, setLoading] = useState(false);
    const [alert, setAlert] = useState(null);

    const handleGenerateReport = async () => {
        setLoading(true);
        try {
            let response;
            const params = { from: dateFrom, to: dateTo };

            switch (reportType) {
                case 'daily':
                    response = await reportApi.getDaily(params);
                    break;
                case 'weekly':
                    response = await reportApi.getWeekly(params);
                    break;
                case 'monthly':
                    response = await reportApi.getMonthly(params);
                    break;
                case 'production':
                    response = await reportApi.getProduction(params);
                    break;
                case 'stock':
                    response = await reportApi.getStockMovement(params);
                    break;
                case 'margin':
                    response = await reportApi.getMargin(params);
                    break;
            }

            setReportData(response.data.data);
        } catch (error) {
            setAlert({ type: 'error', message: 'Gagal mengambil data laporan' });
        } finally {
            setLoading(false);
        }
    };

    const renderReportContent = () => {
        if (!reportData) {
            return (
                <div className="text-center text-gray-500 py-12">
                    <DocumentChartBarIcon className="h-16 w-16 mx-auto text-gray-300 mb-4" />
                    <p>Pilih jenis laporan dan klik "Generate Laporan"</p>
                </div>
            );
        }

        switch (reportType) {
            case 'production':
                return <ProductionReport data={reportData} />;
            case 'stock':
                return <StockMovementReport data={reportData} />;
            case 'margin':
                return <MarginReport data={reportData} />;
            default:
                return <DailyReport data={reportData} />;
        }
    };

    return (
        <div className="space-y-6">
            {alert && (
                <Alert type={alert.type} message={alert.message} />
            )}

            {/* Header */}
            <div>
                <h1 className="text-2xl font-bold text-gray-900">Laporan</h1>
                <p className="text-gray-500">Generate dan analisis laporan operasional</p>
            </div>

            {/* Filters */}
            <Card>
                <CardBody>
                    <div className="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                        <Select
                            label="Jenis Laporan"
                            options={reportTypes}
                            value={reportType}
                            onChange={(e) => {
                                setReportType(e.target.value);
                                setReportData(null);
                            }}
                        />
                        <Input
                            label="Dari Tanggal"
                            type="date"
                            value={dateFrom}
                            onChange={(e) => setDateFrom(e.target.value)}
                        />
                        <Input
                            label="Sampai Tanggal"
                            type="date"
                            value={dateTo}
                            onChange={(e) => setDateTo(e.target.value)}
                        />
                        <Button onClick={handleGenerateReport} loading={loading}>
                            Generate Laporan
                        </Button>
                        {reportData && (
                            <Button variant="outline">
                                <ArrowDownTrayIcon className="h-5 w-5 mr-2" />
                                Export
                            </Button>
                        )}
                    </div>
                </CardBody>
            </Card>

            {/* Report Content */}
            <Card>
                <CardHeader>
                    <h3 className="text-lg font-medium text-gray-900">
                        {reportTypes.find(r => r.value === reportType)?.label}
                    </h3>
                </CardHeader>
                <CardBody>
                    {loading ? <PageLoading /> : renderReportContent()}
                </CardBody>
            </Card>
        </div>
    );
}

function DailyReport({ data }) {
    const summary = data.summary || data;
    const details = data.details || [];

    return (
        <div className="space-y-6">
            {/* Summary Cards */}
            <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div className="bg-blue-50 p-4 rounded-lg">
                    <p className="text-sm text-blue-600">Total Antrian</p>
                    <p className="text-2xl font-bold text-blue-800">{summary.total_queues || 0}</p>
                </div>
                <div className="bg-green-50 p-4 rounded-lg">
                    <p className="text-sm text-green-600">Total Timbangan</p>
                    <p className="text-2xl font-bold text-green-800">{formatNumber(summary.total_weight || 0)} kg</p>
                </div>
                <div className="bg-yellow-50 p-4 rounded-lg">
                    <p className="text-sm text-yellow-600">Output CPO</p>
                    <p className="text-2xl font-bold text-yellow-800">{formatNumber(summary.total_cpo || 0)} kg</p>
                </div>
                <div className="bg-purple-50 p-4 rounded-lg">
                    <p className="text-sm text-purple-600">Revenue</p>
                    <p className="text-2xl font-bold text-purple-800">{formatCurrency(summary.total_revenue || 0)}</p>
                </div>
            </div>

            {/* Chart */}
            {details.length > 0 && (
                <div className="h-80">
                    <ResponsiveContainer width="100%" height="100%">
                        <BarChart data={details}>
                            <CartesianGrid strokeDasharray="3 3" />
                            <XAxis dataKey="date" />
                            <YAxis />
                            <Tooltip />
                            <Legend />
                            <Bar dataKey="weight" name="Berat (kg)" fill="#16a34a" />
                            <Bar dataKey="cpo" name="CPO (kg)" fill="#eab308" />
                        </BarChart>
                    </ResponsiveContainer>
                </div>
            )}
        </div>
    );
}

function ProductionReport({ data }) {
    const productions = Array.isArray(data) ? data : data.productions || [];
    const summary = data.summary || {};

    return (
        <div className="space-y-6">
            {/* Summary */}
            <div className="grid grid-cols-2 md:grid-cols-5 gap-4">
                <div className="bg-gray-50 p-4 rounded-lg text-center">
                    <p className="text-sm text-gray-600">Total Batch</p>
                    <p className="text-2xl font-bold">{summary.total_batches || productions.length}</p>
                </div>
                <div className="bg-green-50 p-4 rounded-lg text-center">
                    <p className="text-sm text-green-600">Total Input</p>
                    <p className="text-2xl font-bold text-green-800">{formatNumber(summary.total_input || 0)}</p>
                </div>
                <div className="bg-yellow-50 p-4 rounded-lg text-center">
                    <p className="text-sm text-yellow-600">Total CPO</p>
                    <p className="text-2xl font-bold text-yellow-800">{formatNumber(summary.total_cpo || 0)}</p>
                </div>
                <div className="bg-orange-50 p-4 rounded-lg text-center">
                    <p className="text-sm text-orange-600">Avg OER</p>
                    <p className="text-2xl font-bold text-orange-800">{parseFloat(summary.avg_oer || 0).toFixed(2)}%</p>
                </div>
                <div className="bg-blue-50 p-4 rounded-lg text-center">
                    <p className="text-sm text-blue-600">Avg KER</p>
                    <p className="text-2xl font-bold text-blue-800">{parseFloat(summary.avg_ker || 0).toFixed(2)}%</p>
                </div>
            </div>

            {/* Extraction Rate Chart */}
            {productions.length > 0 && (
                <div className="h-80">
                    <ResponsiveContainer width="100%" height="100%">
                        <LineChart data={productions}>
                            <CartesianGrid strokeDasharray="3 3" />
                            <XAxis dataKey="date" />
                            <YAxis />
                            <Tooltip />
                            <Legend />
                            <Line type="monotone" dataKey="oer" name="OER (%)" stroke="#16a34a" strokeWidth={2} />
                            <Line type="monotone" dataKey="ker" name="KER (%)" stroke="#eab308" strokeWidth={2} />
                        </LineChart>
                    </ResponsiveContainer>
                </div>
            )}

            {/* Table */}
            <Table>
                <Thead>
                    <Tr>
                        <Th>Tanggal</Th>
                        <Th>Batch</Th>
                        <Th>Input TBS</Th>
                        <Th>Output CPO</Th>
                        <Th>OER</Th>
                        <Th>Output Kernel</Th>
                        <Th>KER</Th>
                    </Tr>
                </Thead>
                <Tbody>
                    {productions.map((prod, index) => (
                        <Tr key={index}>
                            <Td>{formatDate(prod.date || prod.production_date)}</Td>
                            <Td className="font-mono">{prod.batch_number || prod.batch}</Td>
                            <Td>{formatNumber(prod.tbs_input || prod.input)} kg</Td>
                            <Td className="text-yellow-600">{formatNumber(prod.cpo_output || prod.cpo)} kg</Td>
                            <Td>{parseFloat(prod.oer || prod.cpo_extraction_rate || 0).toFixed(2)}%</Td>
                            <Td className="text-orange-600">{formatNumber(prod.kernel_output || prod.kernel)} kg</Td>
                            <Td>{parseFloat(prod.ker || prod.kernel_extraction_rate || 0).toFixed(2)}%</Td>
                        </Tr>
                    ))}
                </Tbody>
            </Table>
        </div>
    );
}

function StockMovementReport({ data }) {
    const movements = Array.isArray(data) ? data : data.movements || [];
    const summary = data.summary || {};

    return (
        <div className="space-y-6">
            {/* Summary */}
            <div className="grid grid-cols-3 gap-4">
                <div className="bg-green-50 p-4 rounded-lg text-center">
                    <p className="text-sm text-green-600">Total Masuk</p>
                    <p className="text-2xl font-bold text-green-800">{formatNumber(summary.total_in || 0)} kg</p>
                </div>
                <div className="bg-red-50 p-4 rounded-lg text-center">
                    <p className="text-sm text-red-600">Total Keluar</p>
                    <p className="text-2xl font-bold text-red-800">{formatNumber(summary.total_out || 0)} kg</p>
                </div>
                <div className="bg-blue-50 p-4 rounded-lg text-center">
                    <p className="text-sm text-blue-600">Saldo Akhir</p>
                    <p className="text-2xl font-bold text-blue-800">{formatNumber(summary.ending_balance || 0)} kg</p>
                </div>
            </div>

            {/* Table */}
            <Table>
                <Thead>
                    <Tr>
                        <Th>Tanggal</Th>
                        <Th>Produk</Th>
                        <Th>Tipe</Th>
                        <Th>Jumlah</Th>
                        <Th>Referensi</Th>
                        <Th>Keterangan</Th>
                    </Tr>
                </Thead>
                <Tbody>
                    {movements.map((mov, index) => (
                        <Tr key={index}>
                            <Td>{formatDate(mov.date)}</Td>
                            <Td>{mov.product?.toUpperCase()}</Td>
                            <Td>
                                <span className={mov.type === 'in' ? 'text-green-600' : 'text-red-600'}>
                                    {mov.type === 'in' ? 'Masuk' : 'Keluar'}
                                </span>
                            </Td>
                            <Td className={mov.type === 'in' ? 'text-green-600' : 'text-red-600'}>
                                {mov.type === 'in' ? '+' : '-'}{formatNumber(mov.quantity)} kg
                            </Td>
                            <Td className="font-mono text-sm">{mov.reference || '-'}</Td>
                            <Td className="text-gray-500">{mov.notes || '-'}</Td>
                        </Tr>
                    ))}
                </Tbody>
            </Table>
        </div>
    );
}

function MarginReport({ data }) {
    const margins = Array.isArray(data) ? data : data.margins || [];
    const summary = data.summary || {};

    return (
        <div className="space-y-6">
            {/* Summary */}
            <div className="grid grid-cols-4 gap-4">
                <div className="bg-blue-50 p-4 rounded-lg text-center">
                    <p className="text-sm text-blue-600">Total Pembelian</p>
                    <p className="text-2xl font-bold text-blue-800">{formatCurrency(summary.total_purchase || 0)}</p>
                </div>
                <div className="bg-green-50 p-4 rounded-lg text-center">
                    <p className="text-sm text-green-600">Total Penjualan</p>
                    <p className="text-2xl font-bold text-green-800">{formatCurrency(summary.total_sales || 0)}</p>
                </div>
                <div className="bg-yellow-50 p-4 rounded-lg text-center">
                    <p className="text-sm text-yellow-600">Gross Margin</p>
                    <p className="text-2xl font-bold text-yellow-800">{formatCurrency(summary.gross_margin || 0)}</p>
                </div>
                <div className="bg-purple-50 p-4 rounded-lg text-center">
                    <p className="text-sm text-purple-600">Margin %</p>
                    <p className="text-2xl font-bold text-purple-800">{parseFloat(summary.margin_percentage || 0).toFixed(2)}%</p>
                </div>
            </div>

            {/* Chart */}
            {margins.length > 0 && (
                <div className="h-80">
                    <ResponsiveContainer width="100%" height="100%">
                        <BarChart data={margins}>
                            <CartesianGrid strokeDasharray="3 3" />
                            <XAxis dataKey="period" />
                            <YAxis />
                            <Tooltip formatter={(value) => formatCurrency(value)} />
                            <Legend />
                            <Bar dataKey="purchase" name="Pembelian" fill="#3b82f6" />
                            <Bar dataKey="sales" name="Penjualan" fill="#22c55e" />
                            <Bar dataKey="margin" name="Margin" fill="#eab308" />
                        </BarChart>
                    </ResponsiveContainer>
                </div>
            )}
        </div>
    );
}
