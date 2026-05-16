import React from 'react';
import { Link } from 'react-router-dom';

interface Values {
    username: string;
    password: string;
}

const LoginContainer = () => {
    return (
        <div className='w-full max-w-md mx-auto'>
            <div className='text-center mb-8'>
                <h2 className='text-3xl font-bold text-neutral-100 tracking-tight'>Tricosia Panel</h2>
            </div>

            <div className='bg-neutral-800 p-8 rounded-lg shadow-xl border border-neutral-700 text-center'>
                <p className='text-sm text-neutral-300 mb-6'>
                    You need to use our central identy provider to gain access to this panel.
                </p>

                <a
                    href='/auth/login/keycloak'
                    className='w-full block text-center bg-blue-600 hover:bg-blue-500 text-white font-semibold py-3 px-4 rounded-md transition duration-150 shadow-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:ring-offset-neutral-800'
                >
                    Login with Keycloak
                </a>
            </div>
        </div>
    );
};

export default LoginContainer;
