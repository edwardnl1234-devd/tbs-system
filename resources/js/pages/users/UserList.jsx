import React, { useState, useEffect } from 'react';
import { PlusIcon, PencilIcon, TrashIcon, MagnifyingGlassIcon } from '@heroicons/react/24/outline';
import { userApi } from '../../services/api';
import { Card, CardBody } from '../../components/ui/Card';
import Button from '../../components/ui/Button';
import Input from '../../components/ui/Input';
import Select from '../../components/ui/Select';
import Modal from '../../components/ui/Modal';
import Badge from '../../components/ui/Badge';
import Table, { Thead, Tbody, Tr, Th, Td } from '../../components/ui/Table';
import Pagination from '../../components/ui/Pagination';
import { PageLoading } from '../../components/ui/Loading';
import Alert from '../../components/ui/Alert';
import { formatDate } from '../../utils/helpers';
import { useAuth } from '../../contexts/AuthContext';

const roles = [
    { value: 'admin', label: 'Admin' },
    { value: 'manager', label: 'Manager' },
    { value: 'mandor', label: 'Mandor' },
    { value: 'accounting', label: 'Accounting' },
    { value: 'operator_timbangan', label: 'Operator Timbangan' },
];

export default function UserList() {
    const { user: currentUser } = useAuth();
    const [users, setUsers] = useState([]);
    const [loading, setLoading] = useState(true);
    const [search, setSearch] = useState('');
    const [currentPage, setCurrentPage] = useState(1);
    const [pagination, setPagination] = useState({});
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [editingUser, setEditingUser] = useState(null);
    const [formData, setFormData] = useState({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        role: '',
        is_active: true,
    });
    const [formErrors, setFormErrors] = useState({});
    const [submitting, setSubmitting] = useState(false);
    const [alert, setAlert] = useState(null);

    useEffect(() => {
        fetchUsers();
    }, [currentPage, search]);

    const fetchUsers = async () => {
        try {
            setLoading(true);
            const response = await userApi.getAll({
                page: currentPage,
                search,
            });
            setUsers(response.data.data || []);
            setPagination(response.data.meta || {});
        } catch (error) {
            showAlert('error', 'Gagal memuat data pengguna');
        } finally {
            setLoading(false);
        }
    };

    const showAlert = (type, message) => {
        setAlert({ type, message });
        setTimeout(() => setAlert(null), 5000);
    };

    const openModal = (user = null) => {
        if (user) {
            setEditingUser(user);
            setFormData({
                name: user.name || '',
                email: user.email || '',
                password: '',
                password_confirmation: '',
                role: user.role || '',
                is_active: user.is_active ?? true,
            });
        } else {
            setEditingUser(null);
            setFormData({
                name: '',
                email: '',
                password: '',
                password_confirmation: '',
                role: '',
                is_active: true,
            });
        }
        setFormErrors({});
        setIsModalOpen(true);
    };

    const closeModal = () => {
        setIsModalOpen(false);
        setEditingUser(null);
        setFormErrors({});
    };

    const handleChange = (e) => {
        const { name, value, type, checked } = e.target;
        setFormData(prev => ({
            ...prev,
            [name]: type === 'checkbox' ? checked : value,
        }));
        if (formErrors[name]) {
            setFormErrors(prev => ({ ...prev, [name]: null }));
        }
    };

    const validateForm = () => {
        const errors = {};
        if (!formData.name) errors.name = 'Nama wajib diisi';
        if (!formData.email) errors.email = 'Email wajib diisi';
        if (!formData.role) errors.role = 'Role wajib dipilih';
        if (!editingUser && !formData.password) errors.password = 'Password wajib diisi';
        if (formData.password && formData.password.length < 8) {
            errors.password = 'Password minimal 8 karakter';
        }
        if (formData.password && formData.password !== formData.password_confirmation) {
            errors.password_confirmation = 'Konfirmasi password tidak cocok';
        }
        setFormErrors(errors);
        return Object.keys(errors).length === 0;
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (!validateForm()) return;

        setSubmitting(true);
        try {
            const data = { ...formData };
            if (!data.password) {
                delete data.password;
                delete data.password_confirmation;
            }

            if (editingUser) {
                await userApi.update(editingUser.id, data);
                showAlert('success', 'Pengguna berhasil diperbarui');
            } else {
                await userApi.create(data);
                showAlert('success', 'Pengguna berhasil ditambahkan');
            }
            closeModal();
            fetchUsers();
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

    const handleDelete = async (user) => {
        if (user.id === currentUser?.id) {
            showAlert('error', 'Tidak dapat menghapus akun sendiri');
            return;
        }

        if (!confirm(`Apakah Anda yakin ingin menghapus pengguna "${user.name}"?`)) {
            return;
        }

        try {
            await userApi.delete(user.id);
            showAlert('success', 'Pengguna berhasil dihapus');
            fetchUsers();
        } catch (error) {
            showAlert('error', 'Gagal menghapus pengguna');
        }
    };

    const getRoleColor = (role) => {
        const colors = {
            admin: 'purple',
            manager: 'blue',
            mandor: 'orange',
            accounting: 'green',
            operator_timbangan: 'gray',
        };
        return colors[role] || 'gray';
    };

    return (
        <div className="space-y-6">
            {alert && (
                <Alert type={alert.type} message={alert.message} />
            )}

            {/* Header */}
            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">Pengguna</h1>
                    <p className="text-gray-500">Kelola akun pengguna sistem</p>
                </div>
                <Button onClick={() => openModal()}>
                    <PlusIcon className="h-5 w-5 mr-2" />
                    Tambah Pengguna
                </Button>
            </div>

            {/* Filters */}
            <Card>
                <CardBody>
                    <div className="relative max-w-md">
                        <MagnifyingGlassIcon className="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                        <input
                            type="text"
                            placeholder="Cari pengguna..."
                            value={search}
                            onChange={(e) => {
                                setSearch(e.target.value);
                                setCurrentPage(1);
                            }}
                            className="pl-10 block w-full rounded-md border-gray-300 shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"
                        />
                    </div>
                </CardBody>
            </Card>

            {/* Table */}
            <Card>
                {loading ? (
                    <PageLoading />
                ) : (
                    <>
                        <Table>
                            <Thead>
                                <Tr>
                                    <Th>Nama</Th>
                                    <Th>Email</Th>
                                    <Th>Role</Th>
                                    <Th>Status</Th>
                                    <Th>Terakhir Login</Th>
                                    <Th>Aksi</Th>
                                </Tr>
                            </Thead>
                            <Tbody>
                                {users.length === 0 ? (
                                    <Tr>
                                        <Td colSpan={6} className="text-center text-gray-500 py-8">
                                            Tidak ada data pengguna
                                        </Td>
                                    </Tr>
                                ) : (
                                    users.map((user) => (
                                        <Tr key={user.id}>
                                            <Td>
                                                <div className="flex items-center">
                                                    <div className="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center">
                                                        <span className="text-green-600 font-medium">
                                                            {user.name?.charAt(0)?.toUpperCase()}
                                                        </span>
                                                    </div>
                                                    <div className="ml-3">
                                                        <div className="font-medium">{user.name}</div>
                                                    </div>
                                                </div>
                                            </Td>
                                            <Td>{user.email}</Td>
                                            <Td>
                                                <Badge color={getRoleColor(user.role)}>
                                                    {user.role?.toUpperCase()}
                                                </Badge>
                                            </Td>
                                            <Td>
                                                <Badge color={user.is_active ? 'green' : 'gray'}>
                                                    {user.is_active ? 'Aktif' : 'Tidak Aktif'}
                                                </Badge>
                                            </Td>
                                            <Td className="text-gray-500">
                                                {user.last_login_at
                                                    ? formatDate(user.last_login_at, 'datetime')
                                                    : '-'}
                                            </Td>
                                            <Td>
                                                <div className="flex items-center gap-2">
                                                    <button
                                                        onClick={() => openModal(user)}
                                                        className="text-blue-600 hover:text-blue-800"
                                                    >
                                                        <PencilIcon className="h-5 w-5" />
                                                    </button>
                                                    {user.id !== currentUser?.id && (
                                                        <button
                                                            onClick={() => handleDelete(user)}
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
                title={editingUser ? 'Edit Pengguna' : 'Tambah Pengguna'}
            >
                <form onSubmit={handleSubmit} className="space-y-4">
                    <Input
                        label="Nama Lengkap"
                        name="name"
                        value={formData.name}
                        onChange={handleChange}
                        error={formErrors.name}
                        required
                    />
                    <Input
                        label="Email"
                        name="email"
                        type="email"
                        value={formData.email}
                        onChange={handleChange}
                        error={formErrors.email}
                        required
                    />
                    <Select
                        label="Role"
                        name="role"
                        value={formData.role}
                        onChange={handleChange}
                        options={roles}
                        error={formErrors.role}
                        required
                    />
                    <Input
                        label={editingUser ? 'Password Baru (kosongkan jika tidak diubah)' : 'Password'}
                        name="password"
                        type="password"
                        value={formData.password}
                        onChange={handleChange}
                        error={formErrors.password}
                        required={!editingUser}
                    />
                    {formData.password && (
                        <Input
                            label="Konfirmasi Password"
                            name="password_confirmation"
                            type="password"
                            value={formData.password_confirmation}
                            onChange={handleChange}
                            error={formErrors.password_confirmation}
                        />
                    )}
                    <div className="flex items-center">
                        <input
                            type="checkbox"
                            id="is_active"
                            name="is_active"
                            checked={formData.is_active}
                            onChange={handleChange}
                            className="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded"
                        />
                        <label htmlFor="is_active" className="ml-2 text-sm text-gray-700">
                            Pengguna Aktif
                        </label>
                    </div>
                    <div className="flex justify-end gap-3 pt-4">
                        <Button variant="secondary" onClick={closeModal}>
                            Batal
                        </Button>
                        <Button type="submit" loading={submitting}>
                            {editingUser ? 'Simpan Perubahan' : 'Tambah Pengguna'}
                        </Button>
                    </div>
                </form>
            </Modal>
        </div>
    );
}
