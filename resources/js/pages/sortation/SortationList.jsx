import React, { useState, useEffect } from 'react';
import { sortationApi } from '../../services/api';
import { Card, CardBody, CardHeader } from '../../components/ui/Card';
import Badge from '../../components/ui/Badge';
import Table, { Thead, Tbody, Tr, Th, Td } from '../../components/ui/Table';
import { PageLoading } from '../../components/ui/Loading';
import Alert from '../../components/ui/Alert';
import { formatDate, formatNumber } from '../../utils/helpers';

export default function SortationList() {
    const [sortations, setSortations] = useState([]);
    const [performance, setPerformance] = useState(null);
    const [loading, setLoading] = useState(true);
    const [alert, setAlert] = useState(null);

    useEffect(() => {
        fetchData();
    }, []);

    const fetchData = async () => {
        try {
            const [sortationsRes, performanceRes] = await Promise.all([
                sortationApi.getToday(),
                sortationApi.getPerformance(),
            ]);
            setSortations(sortationsRes.data.data);
            setPerformance(performanceRes.data.data);
        } catch (error) {
            setAlert({ type: 'error', message: 'Gagal memuat data sortasi' });
        } finally {
            setLoading(false);
        }
    };

    if (loading) return <PageLoading />;

    return (
        <div className="space-y-6">
            {alert && (
                <Alert type={alert.type} message={alert.message} />
            )}

            {/* Header */}
            <div>
                <h1 className="text-2xl font-bold text-gray-900">Sortasi</h1>
                <p className="text-gray-500">Data sortasi TBS hari ini</p>
            </div>

            {/* Performance Cards */}
            {performance && (
                <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <Card>
                        <CardBody className="text-center">
                            <div className="text-3xl font-bold text-green-600">
                                {formatNumber(performance.total_sorted || 0)}
                            </div>
                            <div className="text-sm text-gray-500">Total Sortasi (kg)</div>
                        </CardBody>
                    </Card>
                    <Card>
                        <CardBody className="text-center">
                            <div className="text-3xl font-bold text-blue-600">
                                {performance.ripe_percentage?.toFixed(1) || 0}%
                            </div>
                            <div className="text-sm text-gray-500">Matang</div>
                        </CardBody>
                    </Card>
                    <Card>
                        <CardBody className="text-center">
                            <div className="text-3xl font-bold text-yellow-600">
                                {performance.unripe_percentage?.toFixed(1) || 0}%
                            </div>
                            <div className="text-sm text-gray-500">Mentah</div>
                        </CardBody>
                    </Card>
                    <Card>
                        <CardBody className="text-center">
                            <div className="text-3xl font-bold text-red-600">
                                {performance.reject_percentage?.toFixed(1) || 0}%
                            </div>
                            <div className="text-sm text-gray-500">Reject</div>
                        </CardBody>
                    </Card>
                </div>
            )}

            {/* Sortation Table */}
            <Card>
                <CardHeader>
                    <h3 className="text-lg font-medium text-gray-900">Riwayat Sortasi Hari Ini</h3>
                </CardHeader>
                <Table>
                    <Thead>
                        <Tr>
                            <Th>No. Tiket</Th>
                            <Th>Supplier</Th>
                            <Th>Berat Awal</Th>
                            <Th>Matang</Th>
                            <Th>Mentah</Th>
                            <Th>Reject</Th>
                            <Th>Waktu</Th>
                        </Tr>
                    </Thead>
                    <Tbody>
                        {sortations.length === 0 ? (
                            <Tr>
                                <Td colSpan={7} className="text-center text-gray-500 py-8">
                                    Belum ada data sortasi hari ini
                                </Td>
                            </Tr>
                        ) : (
                            sortations.map((sortation) => (
                                <Tr key={sortation.id}>
                                    <Td className="font-mono font-medium">
                                        {sortation.weighing?.ticket_number}
                                    </Td>
                                    <Td>{sortation.weighing?.queue?.supplier?.name}</Td>
                                    <Td>{formatNumber(sortation.total_weight)} kg</Td>
                                    <Td>
                                        <span className="text-green-600 font-medium">
                                            {formatNumber(sortation.ripe_weight)} kg
                                        </span>
                                        <span className="text-gray-400 text-sm ml-1">
                                            ({((sortation.ripe_weight / sortation.total_weight) * 100).toFixed(1)}%)
                                        </span>
                                    </Td>
                                    <Td>
                                        <span className="text-yellow-600 font-medium">
                                            {formatNumber(sortation.unripe_weight)} kg
                                        </span>
                                    </Td>
                                    <Td>
                                        <span className="text-red-600 font-medium">
                                            {formatNumber(sortation.reject_weight)} kg
                                        </span>
                                    </Td>
                                    <Td className="text-gray-500">
                                        {formatDate(sortation.created_at, 'time')}
                                    </Td>
                                </Tr>
                            ))
                        )}
                    </Tbody>
                </Table>
            </Card>
        </div>
    );
}
