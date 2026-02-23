import React, { useState, useEffect } from 'react';
import { stockPurchaseApi } from '../../services/api';
import { Card, CardBody, CardHeader } from '../../components/ui/Card';
import Table, { Thead, Tbody, Tr, Th, Td } from '../../components/ui/Table';
import Button from '../../components/ui/Button';
import Input from '../../components/ui/Input';
import Select from '../../components/ui/Select';
import Badge from '../../components/ui/Badge';
import { PageLoading } from '../../components/ui/Loading';
import Alert from '../../components/ui/Alert';
import { formatNumber, formatDate, formatCurrency } from '../../utils/helpers';

export default function PurchaseHistory() {
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [alert, setAlert] = useState(null);
    const [filters, setFilters] = useState({
        date_from: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0],
        date_to: new Date().toISOString().split('T')[0],
        type: '',
    });

    useEffect(() => {
        fetchData();
    }, []);

    const fetchData = async () => {
        setLoading(true);
        try {
            const response = await stockPurchaseApi.getHistory(filters);
            setData(response.data.data);
        } catch (error) {
            setAlert({ type: 'error', message: 'Gagal memuat riwayat pembelian' });
        } finally {
            setLoading(false);
        }
    };

    const handleFilterChange = (e) => {
        const { name, value } = e.target;
        setFilters(prev => ({ ...prev, [name]: value }));
    };

    const handleFilter = () => {
        fetchData();
    };

    const exportToCsv = () => {
        if (!data?.transactions?.length) return;

        const headers = ['Tanggal', 'Tipe', 'No. Referensi', 'Supplier', 'Quantity (kg)', 'Harga/kg', 'Total', 'Catatan'];
        const rows = data.transactions.map(t => [
            t.stock_date,
            t.type,
            t.reference_number || '-',
            t.supplier_name,
            t.quantity,
            t.purchase_price || 0,
            t.total_value,
            t.notes || '',
        ]);

        const csvContent = [
            headers.join(','),
            ...rows.map(row => row.map(cell => `"${cell}"`).join(','))
        ].join('\n');

        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = `riwayat-pembelian-${filters.date_from}-${filters.date_to}.csv`;
        link.click();
    };

    const typeOptions = [
        { value: '', label: 'Semua Tipe' },
        { value: 'cpo', label: 'CPO' },
        { value: 'kernel', label: 'Kernel' },
        { value: 'shell', label: 'Shell' },
    ];

    if (loading) return <PageLoading />;

    return (
        <div className="space-y-6">
            {alert && (
                <Alert 
                    type={alert.type} 
                    message={alert.message} 
                    onClose={() => setAlert(null)}
                />
            )}

            {/* Header */}
            <div className="flex justify-between items-center">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">Riwayat Pembelian</h1>
                    <p className="text-gray-500">Log pembelian stok untuk pembukuan</p>
                </div>
                <Button variant="secondary" onClick={exportToCsv} disabled={!data?.transactions?.length}>
                    📥 Export CSV
                </Button>
            </div>

            {/* Filters */}
            <Card>
                <CardBody>
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                        <Input
                            label="Dari Tanggal"
                            type="date"
                            name="date_from"
                            value={filters.date_from}
                            onChange={handleFilterChange}
                        />
                        <Input
                            label="Sampai Tanggal"
                            type="date"
                            name="date_to"
                            value={filters.date_to}
                            onChange={handleFilterChange}
                        />
                        <Select
                            label="Tipe Produk"
                            name="type"
                            value={filters.type}
                            onChange={handleFilterChange}
                            options={typeOptions}
                        />
                        <Button onClick={handleFilter}>
                            Filter
                        </Button>
                    </div>
                </CardBody>
            </Card>

            {/* Summary Cards */}
            {data?.totals && (
                <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <Card>
                        <CardBody className="text-center">
                            <div className="text-3xl font-bold text-blue-600">
                                {data.totals.total_transactions}
                            </div>
                            <div className="text-sm text-gray-500 mt-1">Total Transaksi</div>
                        </CardBody>
                    </Card>
                    <Card>
                        <CardBody className="text-center">
                            <div className="text-3xl font-bold text-green-600">
                                {formatNumber(data.totals.total_quantity)} kg
                            </div>
                            <div className="text-sm text-gray-500 mt-1">Total Quantity</div>
                        </CardBody>
                    </Card>
                    <Card>
                        <CardBody className="text-center">
                            <div className="text-2xl font-bold text-purple-600">
                                {formatCurrency(data.totals.total_value)}
                            </div>
                            <div className="text-sm text-gray-500 mt-1">Total Nilai Pembelian</div>
                        </CardBody>
                    </Card>
                    <Card>
                        <CardBody className="text-center">
                            <div className="text-sm space-y-1">
                                <div className="flex justify-between">
                                    <span className="text-yellow-600">CPO:</span>
                                    <span>{formatNumber(data.totals.by_type.cpo.quantity)} kg</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-orange-600">Kernel:</span>
                                    <span>{formatNumber(data.totals.by_type.kernel.quantity)} kg</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-gray-600">Shell:</span>
                                    <span>{formatNumber(data.totals.by_type.shell.quantity)} kg</span>
                                </div>
                            </div>
                            <div className="text-xs text-gray-400 mt-2">Per Tipe Produk</div>
                        </CardBody>
                    </Card>
                </div>
            )}

            {/* Period Info */}
            {data?.period && (
                <div className="text-sm text-gray-500">
                    Menampilkan data periode: <strong>{formatDate(data.period.from)}</strong> - <strong>{formatDate(data.period.to)}</strong>
                </div>
            )}

            {/* Transactions Table */}
            <Card>
                <CardHeader>
                    <h3 className="text-lg font-medium">Detail Transaksi</h3>
                </CardHeader>
                <CardBody>
                    {!data?.transactions?.length ? (
                        <div className="text-center py-8 text-gray-500">
                            Tidak ada data pembelian untuk periode ini
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <Table>
                                <Thead>
                                    <Tr>
                                        <Th>Tanggal</Th>
                                        <Th>Tipe</Th>
                                        <Th>No. Referensi</Th>
                                        <Th>Supplier</Th>
                                        <Th className="text-right">Qty (kg)</Th>
                                        <Th className="text-right">Harga/kg</Th>
                                        <Th className="text-right">Total</Th>
                                        <Th>Grade</Th>
                                    </Tr>
                                </Thead>
                                <Tbody>
                                    {data.transactions.map((item, index) => (
                                        <Tr key={`${item.type}-${item.id}-${index}`}>
                                            <Td>{formatDate(item.stock_date)}</Td>
                                            <Td>
                                                <Badge variant={
                                                    item.type === 'CPO' ? 'warning' : 
                                                    item.type === 'Kernel' ? 'info' : 'secondary'
                                                }>
                                                    {item.type}
                                                </Badge>
                                            </Td>
                                            <Td className="font-mono text-sm">
                                                {item.reference_number || '-'}
                                            </Td>
                                            <Td>
                                                <div className="font-medium">{item.supplier_name}</div>
                                                <div className="text-xs text-gray-500">{item.supplier_code}</div>
                                            </Td>
                                            <Td className="text-right">{formatNumber(item.quantity)}</Td>
                                            <Td className="text-right">{formatCurrency(item.purchase_price || 0)}</Td>
                                            <Td className="text-right font-medium">{formatCurrency(item.total_value)}</Td>
                                            <Td>
                                                {item.quality_grade ? (
                                                    <Badge variant={
                                                        item.quality_grade === 'premium' ? 'success' : 
                                                        item.quality_grade === 'standard' ? 'info' : 'warning'
                                                    }>
                                                        {item.quality_grade}
                                                    </Badge>
                                                ) : '-'}
                                            </Td>
                                        </Tr>
                                    ))}
                                </Tbody>
                            </Table>
                        </div>
                    )}
                </CardBody>
            </Card>
        </div>
    );
}
