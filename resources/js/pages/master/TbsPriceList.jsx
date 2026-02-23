import React, { useState, useEffect } from 'react';
import { PlusIcon, PencilIcon, TrashIcon } from '@heroicons/react/24/outline';
import { useAuth } from '../../contexts/AuthContext';
import { tbsPriceApi } from '../../services/api';
import { Card, CardBody, CardHeader } from '../../components/ui/Card';
import Button from '../../components/ui/Button';
import Input from '../../components/ui/Input';
import Select from '../../components/ui/Select';
import Modal from '../../components/ui/Modal';
import Badge from '../../components/ui/Badge';
import Table, { Thead, Tbody, Tr, Th, Td } from '../../components/ui/Table';
import Pagination from '../../components/ui/Pagination';
import { PageLoading } from '../../components/ui/Loading';
import Alert from '../../components/ui/Alert';
import { formatCurrency, formatDate } from '../../utils/helpers';

const supplierTypes = [
    { value: 'plasma', label: 'Plasma' },
    { value: 'inti', label: 'Inti' },
    { value: 'umum', label: 'Umum/Masyarakat' },
];

export default function TbsPriceList() {
    const { user } = useAuth();
    const [prices, setPrices] = useState([]);
    const [todayPrice, setTodayPrice] = useState(null);
    const [loading, setLoading] = useState(true);
    const [currentPage, setCurrentPage] = useState(1);
    const [pagination, setPagination] = useState({});
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [editingPrice, setEditingPrice] = useState(null);
    const [formData, setFormData] = useState({
        effective_date: new Date().toISOString().split('T')[0],
        supplier_type: '',
        price_per_kg: '',
        notes: '',
    });
    const [formErrors, setFormErrors] = useState({});
    const [submitting, setSubmitting] = useState(false);
    const [alert, setAlert] = useState(null);

    useEffect(() => {
        fetchPrices();
        fetchTodayPrice();
    }, [currentPage]);

    const fetchPrices = async () => {
        try {
            setLoading(true);
            const response = await tbsPriceApi.getAll({
                page: currentPage,
            });
            setPrices(response.data.data || []);
            setPagination(response.data.meta || {});
        } catch (error) {
            showAlert('error', 'Gagal memuat data harga TBS');
        } finally {
            setLoading(false);
        }
    };

    const fetchTodayPrice = async () => {
        try {
            const response = await tbsPriceApi.getToday();
            setTodayPrice(response.data.data);
        } catch (error) {
            // Today's price might not exist yet
        }
    };

    const showAlert = (type, message) => {
        setAlert({ type, message });
        setTimeout(() => setAlert(null), 5000);
    };

    const openModal = (price = null) => {
        if (price) {
            setEditingPrice(price);
            setFormData({
                effective_date: price.effective_date || new Date().toISOString().split('T')[0],
                supplier_type: price.supplier_type || '',
                price_per_kg: price.price_per_kg || '',
                notes: price.notes || '',
            });
        } else {
            setEditingPrice(null);
            setFormData({
                effective_date: new Date().toISOString().split('T')[0],
                supplier_type: '',
                price_per_kg: '',
                notes: '',
            });
        }
        setFormErrors({});
        setIsModalOpen(true);
    };

    const closeModal = () => {
        setIsModalOpen(false);
        setEditingPrice(null);
        setFormErrors({});
    };

    const handleChange = (e) => {
        const { name, value } = e.target;
        setFormData(prev => ({
            ...prev,
            [name]: value,
        }));
        if (formErrors[name]) {
            setFormErrors(prev => ({ ...prev, [name]: null }));
        }
    };

    const validateForm = () => {
        const errors = {};
        if (!formData.effective_date) errors.effective_date = 'Tanggal wajib diisi';
        if (!formData.supplier_type) errors.supplier_type = 'Tipe supplier wajib dipilih';
        if (!formData.price_per_kg) errors.price_per_kg = 'Harga wajib diisi';
        setFormErrors(errors);
        return Object.keys(errors).length === 0;
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (!validateForm()) return;

        setSubmitting(true);
        try {
            if (editingPrice) {
                await tbsPriceApi.update(editingPrice.id, formData);
                showAlert('success', 'Harga TBS berhasil diperbarui');
            } else {
                await tbsPriceApi.create(formData);
                showAlert('success', 'Harga TBS berhasil ditambahkan');
            }
            closeModal();
            fetchPrices();
            fetchTodayPrice();
        } catch (error) {
            const message = error.response?.data?.message || 'Terjadi kesalahan';
            showAlert('error', message);
            if (error.response?.data?.errors) {
                setFormErrors(error.response.data.errors);
            }
        } finally {
            setSubmitting(false);
        }
    };

    const handleDelete = async (price) => {
        if (!confirm('Apakah Anda yakin ingin menghapus harga TBS ini?')) {
            return;
        }

        try {
            await tbsPriceApi.delete(price.id);
            showAlert('success', 'Harga TBS berhasil dihapus');
            fetchPrices();
            fetchTodayPrice();
        } catch (error) {
            showAlert('error', 'Gagal menghapus harga TBS');
        }
    };

    return (
        <div className="space-y-6">
            {alert && (
                <Alert type={alert.type} message={alert.message} />
            )}

            {/* Header */}
            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">Harga TBS</h1>
                    <p className="text-gray-500">Kelola harga TBS berdasarkan tipe supplier</p>
                </div>
                {/* Tambah Harga: Admin only */}
                {user?.role === 'admin' && (
                    <Button onClick={() => openModal()}>
                        <PlusIcon className="h-5 w-5 mr-2" />
                        Tambah Harga
                    </Button>
                )}
            </div>

            {/* Today's Prices */}
            {todayPrice && (
                <Card>
                    <CardHeader>
                        <h3 className="text-lg font-medium text-gray-900">Harga Hari Ini</h3>
                    </CardHeader>
                    <CardBody>
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                            {Array.isArray(todayPrice) ? todayPrice.map((price, index) => (
                                <div key={index} className="bg-green-50 p-4 rounded-lg">
                                    <p className="text-sm text-green-600 font-medium uppercase">
                                        {price.supplier_type}
                                    </p>
                                    <p className="text-2xl font-bold text-green-800">
                                        {formatCurrency(price.price_per_kg)}
                                    </p>
                                    <p className="text-sm text-green-600">per kg</p>
                                </div>
                            )) : (
                                <div className="bg-green-50 p-4 rounded-lg">
                                    <p className="text-sm text-green-600 font-medium uppercase">
                                        {todayPrice.supplier_type}
                                    </p>
                                    <p className="text-2xl font-bold text-green-800">
                                        {formatCurrency(todayPrice.price_per_kg)}
                                    </p>
                                    <p className="text-sm text-green-600">per kg</p>
                                </div>
                            )}
                        </div>
                    </CardBody>
                </Card>
            )}

            {/* Table */}
            <Card>
                <CardHeader>
                    <h3 className="text-lg font-medium text-gray-900">Riwayat Harga</h3>
                </CardHeader>
                {loading ? (
                    <PageLoading />
                ) : (
                    <>
                        <Table>
                            <Thead>
                                <Tr>
                                    <Th>Tanggal Berlaku</Th>
                                    <Th>Tipe Supplier</Th>
                                    <Th>Harga/kg</Th>
                                    <Th>Catatan</Th>
                                    <Th>Aksi</Th>
                                </Tr>
                            </Thead>
                            <Tbody>
                                {prices.length === 0 ? (
                                    <Tr>
                                        <Td colSpan={5} className="text-center text-gray-500 py-8">
                                            Tidak ada data harga TBS
                                        </Td>
                                    </Tr>
                                ) : (
                                    prices.map((price) => (
                                        <Tr key={price.id}>
                                            <Td>{formatDate(price.effective_date)}</Td>
                                            <Td>
                                                <Badge color={price.supplier_type === 'inti' ? 'green' : price.supplier_type === 'plasma' ? 'blue' : 'yellow'}>
                                                    {price.supplier_type?.toUpperCase()}
                                                </Badge>
                                            </Td>
                                            <Td className="font-medium">
                                                {formatCurrency(price.price_per_kg)}
                                            </Td>
                                            <Td className="text-gray-500">{price.notes || '-'}</Td>
                                            <Td>
                                                <div className="flex items-center gap-2">
                                                    {/* Edit: Admin only */}
                                                    {user?.role === 'admin' && (
                                                        <button
                                                            onClick={() => openModal(price)}
                                                            className="text-blue-600 hover:text-blue-800"
                                                        >
                                                            <PencilIcon className="h-5 w-5" />
                                                        </button>
                                                    )}
                                                    {/* Delete: Admin only */}
                                                    {user?.role === 'admin' && (
                                                        <button
                                                            onClick={() => handleDelete(price)}
                                                            className="text-red-600 hover:text-red-800"
                                                        >
                                                            <TrashIcon className="h-5 w-5" />
                                                        </button>
                                                    )}
                                                </div>
                                            </Td>
                                        </Tr>
                                    ))
                                )}
                            </Tbody>
                        </Table>
                        {pagination.last_page > 1 && (
                            <Pagination
                                currentPage={currentPage}
                                totalPages={pagination.last_page}
                                totalItems={pagination.total}
                                itemsPerPage={pagination.per_page}
                                onPageChange={setCurrentPage}
                            />
                        )}
                    </>
                )}
            </Card>

            {/* Modal Form */}
            <Modal
                isOpen={isModalOpen}
                onClose={closeModal}
                title={editingPrice ? 'Edit Harga TBS' : 'Tambah Harga TBS'}
            >
                <form onSubmit={handleSubmit} className="space-y-4">
                    <Input
                        label="Tanggal Berlaku"
                        name="effective_date"
                        type="date"
                        value={formData.effective_date}
                        onChange={handleChange}
                        error={formErrors.effective_date}
                        required
                    />
                    <Select
                        label="Tipe Supplier"
                        name="supplier_type"
                        value={formData.supplier_type}
                        onChange={handleChange}
                        options={supplierTypes}
                        error={formErrors.supplier_type}
                        required
                    />
                    <Input
                        label="Harga per Kg (Rp)"
                        name="price_per_kg"
                        type="number"
                        value={formData.price_per_kg}
                        onChange={handleChange}
                        error={formErrors.price_per_kg}
                        required
                    />
                    <Input
                        label="Catatan"
                        name="notes"
                        value={formData.notes}
                        onChange={handleChange}
                    />
                    <div className="flex justify-end gap-3 pt-4">
                        <Button variant="secondary" onClick={closeModal}>
                            Batal
                        </Button>
                        <Button type="submit" loading={submitting}>
                            {editingPrice ? 'Simpan Perubahan' : 'Tambah Harga'}
                        </Button>
                    </div>
                </form>
            </Modal>
        </div>
    );
}
