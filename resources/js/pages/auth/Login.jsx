import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../../contexts/AuthContext';
import { CubeIcon, EyeIcon, EyeSlashIcon } from '@heroicons/react/24/outline';
import Button from '../../components/ui/Button';
import Alert from '../../components/ui/Alert';

export default function Login() {
    const navigate = useNavigate();
    const { login } = useAuth();
    const [formData, setFormData] = useState({
        email: '',
        password: '',
    });
    const [showPassword, setShowPassword] = useState(false);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');

    const handleChange = (e) => {
        const { name, value } = e.target;
        setFormData(prev => ({ ...prev, [name]: value }));
        setError('');
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setLoading(true);
        setError('');

        try {
            await login(formData);
            navigate('/');
        } catch (err) {
            setError(
                err.response?.data?.message || 
                'Email atau password salah. Silakan coba lagi.'
            );
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="min-h-screen flex">
            {/* Left side - Branding */}
            <div className="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-green-600 to-green-800 p-12 flex-col justify-between">
                <div>
                    <div className="flex items-center text-white">
                        <CubeIcon className="h-10 w-10" />
                        <span className="ml-3 text-2xl font-bold">TBS System</span>
                    </div>
                </div>
                <div className="text-white">
                    <h1 className="text-4xl font-bold mb-4">
                        Sistem Manajemen<br />Tandan Buah Segar
                    </h1>
                    <p className="text-green-100 text-lg">
                        Kelola antrian, timbangan, produksi, dan stok CPO/Kernel/Shell 
                        secara efisien dalam satu platform terintegrasi.
                    </p>
                </div>
                <div className="text-green-200 text-sm">
                    © 2026 TBS System. All rights reserved.
                </div>
            </div>

            {/* Right side - Login Form */}
            <div className="flex-1 flex items-center justify-center p-8 bg-gray-50">
                <div className="w-full max-w-md">
                    {/* Mobile Logo */}
                    <div className="lg:hidden flex items-center justify-center mb-8">
                        <CubeIcon className="h-12 w-12 text-green-600" />
                        <span className="ml-3 text-2xl font-bold text-gray-900">TBS System</span>
                    </div>

                    <div className="bg-white rounded-xl shadow-lg p-8">
                        <div className="text-center mb-8">
                            <h2 className="text-2xl font-bold text-gray-900">
                                Masuk ke Akun Anda
                            </h2>
                            <p className="mt-2 text-gray-600">
                                Silakan masukkan kredensial untuk melanjutkan
                            </p>
                        </div>

                        {error && (
                            <Alert 
                                type="error" 
                                message={error} 
                                className="mb-6"
                            />
                        )}

                        <form onSubmit={handleSubmit} className="space-y-6">
                            <div>
                                <label 
                                    htmlFor="email" 
                                    className="block text-sm font-medium text-gray-700 mb-1"
                                >
                                    Email
                                </label>
                                <input
                                    id="email"
                                    name="email"
                                    type="email"
                                    autoComplete="email"
                                    required
                                    value={formData.email}
                                    onChange={handleChange}
                                    className="block w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors"
                                    placeholder="nama@email.com"
                                />
                            </div>

                            <div>
                                <label 
                                    htmlFor="password" 
                                    className="block text-sm font-medium text-gray-700 mb-1"
                                >
                                    Password
                                </label>
                                <div className="relative">
                                    <input
                                        id="password"
                                        name="password"
                                        type={showPassword ? 'text' : 'password'}
                                        autoComplete="current-password"
                                        required
                                        value={formData.password}
                                        onChange={handleChange}
                                        className="block w-full px-4 py-3 pr-12 rounded-lg border border-gray-300 focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors"
                                        placeholder="••••••••"
                                    />
                                    <button
                                        type="button"
                                        onClick={() => setShowPassword(!showPassword)}
                                        className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                                    >
                                        {showPassword ? (
                                            <EyeSlashIcon className="h-5 w-5" />
                                        ) : (
                                            <EyeIcon className="h-5 w-5" />
                                        )}
                                    </button>
                                </div>
                            </div>

                            <div className="flex items-center justify-between">
                                <div className="flex items-center">
                                    <input
                                        id="remember"
                                        name="remember"
                                        type="checkbox"
                                        className="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded"
                                    />
                                    <label htmlFor="remember" className="ml-2 block text-sm text-gray-700">
                                        Ingat saya
                                    </label>
                                </div>
                            </div>

                            <Button
                                type="submit"
                                loading={loading}
                                className="w-full py-3"
                            >
                                Masuk
                            </Button>
                        </form>
                    </div>

                    <p className="mt-8 text-center text-sm text-gray-500 lg:hidden">
                        © 2026 TBS System. All rights reserved.
                    </p>
                </div>
            </div>
        </div>
    );
}
