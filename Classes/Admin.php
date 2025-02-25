<?php
    class Admin extends User {
        public function manageUsers() {
            return User::getAll();
        }

        public function viewSystemStats() {
            return [
                'totalUsers' => User::count(),
                'totalLoans' => LoanRequest::count(),
                'activeLenders' => Lender::count(),
            ];
        }
    }
