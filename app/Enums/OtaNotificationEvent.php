<?php

namespace App\Enums;

enum OtaNotificationEvent: string
{
    // Booking
    case BookingRequestReceived = 'booking_request_received';
    case BookingConfirmed = 'booking_confirmed';
    case BookingStatusChanged = 'booking_status_changed';
    case BookingAssigned = 'booking_assigned';
    case BookingCancelled = 'booking_cancelled';
    case BookingFailedValidation = 'booking_failed_validation';

    // Payment / refund
    case PaymentProofSubmitted = 'payment_proof_submitted';
    case PaymentRecorded = 'payment_recorded';
    case PaymentVerified = 'payment_verified';
    case PaymentRejected = 'payment_rejected';
    case PaymentCompleted = 'payment_completed';
    case RefundRequested = 'refund_requested';
    case RefundApproved = 'refund_approved';
    case RefundPaid = 'refund_paid';
    case RefundRejected = 'refund_rejected';

    // Supplier / ticketing
    case SupplierBookingCreated = 'supplier_booking_created';
    case SupplierBookingFailed = 'supplier_booking_failed';
    case SupplierReadinessFailed = 'supplier_readiness_failed';
    case SupplierSearchFailed = 'supplier_search_failed';
    case SupplierOrderFailed = 'supplier_order_failed';
    case FxConversionFailed = 'fx_conversion_failed';
    case TicketIssued = 'ticket_issued';
    case TicketingFailed = 'ticketing_failed';
    case TicketingNotSupported = 'ticketing_not_supported';

    // User/account/security
    case CustomerRegistered = 'customer_registered';
    case AgentApplicationSubmitted = 'agent_application_submitted';
    case AgentApplicationApproved = 'agent_application_approved';
    case AgentApplicationRejected = 'agent_application_rejected';
    case StaffCreated = 'staff_created';
    case AgentCreated = 'agent_created';
    case AdminCreated = 'admin_created';
    case UserSuspended = 'user_suspended';
    case UserActivated = 'user_activated';
    case PasswordResetRequested = 'password_reset_requested';
    case AdminLoginSuccess = 'admin_login_success';
    case StaffLoginSuccess = 'staff_login_success';
    case AgentLoginSuccess = 'agent_login_success';
    case LoginFailedSensitive = 'login_failed_sensitive';

    // Commission / docs
    case CommissionEarned = 'commission_earned';
    case CommissionApproved = 'commission_approved';
    case CommissionPayoutRecorded = 'commission_payout_recorded';
    case CommissionStatementIssued = 'commission_statement_issued';
    case DocumentGenerated = 'document_generated';
    case DocumentDownloadedAdminOptional = 'document_downloaded_admin_optional';
    case TicketItineraryGenerated = 'ticket_itinerary_generated';
    case InvoiceGenerated = 'invoice_generated';
    case PaymentReceiptGenerated = 'payment_receipt_generated';

    // Reports
    case DailyAdminReport = 'daily_admin_report';
    case WeeklyAdminReport = 'weekly_admin_report';
    case MonthlyAdminReport = 'monthly_admin_report';
    case MonthlyAgentLedger = 'monthly_agent_ledger';
    case MonthlyFinanceLedger = 'monthly_finance_ledger';

    public function defaultScope(): string
    {
        return match ($this) {
            self::BookingRequestReceived,
            self::BookingConfirmed,
            self::BookingStatusChanged,
            self::BookingAssigned,
            self::BookingCancelled,
            self::BookingFailedValidation,
            self::PaymentProofSubmitted,
            self::PaymentRecorded,
            self::PaymentVerified,
            self::PaymentRejected,
            self::PaymentCompleted,
            self::RefundRequested,
            self::RefundApproved,
            self::RefundPaid,
            self::RefundRejected,
            self::SupplierBookingCreated,
            self::SupplierBookingFailed,
            self::SupplierReadinessFailed,
            self::SupplierSearchFailed,
            self::SupplierOrderFailed,
            self::FxConversionFailed,
            self::TicketIssued,
            self::TicketingFailed,
            self::TicketingNotSupported,
            self::AdminLoginSuccess,
            self::StaffLoginSuccess,
            self::AgentLoginSuccess,
            self::LoginFailedSensitive,
            self::DailyAdminReport,
            self::WeeklyAdminReport,
            self::MonthlyAdminReport,
            self::MonthlyAgentLedger,
            self::MonthlyFinanceLedger => 'admin',

            self::CustomerRegistered => 'customer',

            self::AgentApplicationSubmitted,
            self::AgentApplicationApproved,
            self::AgentApplicationRejected,
            self::AgentCreated,
            self::CommissionEarned,
            self::CommissionApproved,
            self::CommissionPayoutRecorded,
            self::CommissionStatementIssued => 'agent',

            self::StaffCreated,
            self::AdminCreated,
            self::UserSuspended,
            self::UserActivated,
            self::PasswordResetRequested,
            self::DocumentGenerated,
            self::DocumentDownloadedAdminOptional,
            self::TicketItineraryGenerated,
            self::InvoiceGenerated,
            self::PaymentReceiptGenerated => 'staff',
        };
    }
}
