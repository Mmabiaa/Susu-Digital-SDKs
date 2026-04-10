"""
Pydantic data-models for the Susu Digital Python SDK.

All models are grouped by domain and use snake_case field names to follow
Python conventions. Aliases are applied where the REST API uses camelCase.
"""

from __future__ import annotations

from datetime import date, datetime
from decimal import Decimal
from enum import Enum
from typing import Any, Dict, List, Optional

from pydantic import BaseModel, ConfigDict, Field, field_validator, model_validator


# ---------------------------------------------------------------------------
# Shared / base
# ---------------------------------------------------------------------------


class SusuBaseModel(BaseModel):
    """Base model with a shared Pydantic v2 configuration."""

    model_config = ConfigDict(
        populate_by_name=True,      # accept both alias & Python name
        str_strip_whitespace=True,
        use_enum_values=True,
        arbitrary_types_allowed=True,
    )

    def to_dict(self) -> Dict[str, Any]:
        """Serialize to a plain dict (excludes ``None`` values by default)."""
        return self.model_dump(exclude_none=True, by_alias=False)


# ---------------------------------------------------------------------------
# Enums
# ---------------------------------------------------------------------------


class Environment(str, Enum):
    SANDBOX = "sandbox"
    PRODUCTION = "production"


class CustomerStatus(str, Enum):
    ACTIVE = "active"
    INACTIVE = "inactive"
    SUSPENDED = "suspended"
    PENDING = "pending"


class TransactionType(str, Enum):
    DEPOSIT = "deposit"
    WITHDRAWAL = "withdrawal"
    TRANSFER = "transfer"


class TransactionStatus(str, Enum):
    PENDING = "pending"
    PROCESSING = "processing"
    COMPLETED = "completed"
    FAILED = "failed"
    REVERSED = "reversed"


class LoanStatus(str, Enum):
    PENDING = "pending"
    UNDER_REVIEW = "under_review"
    APPROVED = "approved"
    DISBURSED = "disbursed"
    ACTIVE = "active"
    CLOSED = "closed"
    DEFAULTED = "defaulted"
    REJECTED = "rejected"


class SavingsAccountType(str, Enum):
    REGULAR = "regular"
    FIXED = "fixed"
    SUSU = "susu"


class CollateralType(str, Enum):
    PROPERTY = "property"
    VEHICLE = "vehicle"
    EQUIPMENT = "equipment"
    SAVINGS = "savings"
    OTHER = "other"


# ---------------------------------------------------------------------------
# Customer models
# ---------------------------------------------------------------------------


class Address(SusuBaseModel):
    street: str
    city: str
    region: str
    country: str
    postal_code: Optional[str] = None


class Identification(SusuBaseModel):
    type: str  # e.g. 'national_id', 'passport', 'voter_id'
    number: str
    expiry_date: Optional[date] = None
    issue_date: Optional[date] = None


class CustomerCreate(SusuBaseModel):
    """Payload for creating a new customer."""

    first_name: str = Field(..., min_length=1, max_length=100)
    last_name: str = Field(..., min_length=1, max_length=100)
    phone: str = Field(..., description="E.164 format, e.g. +233XXXXXXXXX")
    email: Optional[str] = None
    date_of_birth: Optional[date] = None
    address: Optional[Address] = None
    identification: Optional[Identification] = None
    metadata: Dict[str, Any] = Field(default_factory=dict)

    @field_validator("phone")
    @classmethod
    def validate_phone(cls, v: str) -> str:
        if not v.startswith("+"):
            raise ValueError("Phone must be in E.164 format (e.g. +233XXXXXXXXX)")
        return v


class CustomerUpdate(SusuBaseModel):
    """Payload for updating an existing customer (all fields optional)."""

    first_name: Optional[str] = Field(None, min_length=1, max_length=100)
    last_name: Optional[str] = Field(None, min_length=1, max_length=100)
    phone: Optional[str] = None
    email: Optional[str] = None
    address: Optional[Address] = None
    metadata: Optional[Dict[str, Any]] = None


class Customer(SusuBaseModel):
    """Customer resource returned by the API."""

    id: str
    first_name: str
    last_name: str
    phone: str
    email: Optional[str] = None
    date_of_birth: Optional[date] = None
    status: CustomerStatus = CustomerStatus.ACTIVE
    address: Optional[Address] = None
    identification: Optional[Identification] = None
    metadata: Dict[str, Any] = Field(default_factory=dict)
    created_at: Optional[datetime] = None
    updated_at: Optional[datetime] = None

    @property
    def full_name(self) -> str:
        return f"{self.first_name} {self.last_name}"


class Balance(SusuBaseModel):
    customer_id: str
    currency: str = "GHS"
    available: Decimal
    ledger: Decimal
    pending: Decimal = Decimal("0.00")
    as_of: Optional[datetime] = None


# ---------------------------------------------------------------------------
# Transaction models
# ---------------------------------------------------------------------------


class DepositRequest(SusuBaseModel):
    customer_id: str
    amount: Decimal = Field(..., gt=0, description="Must be a positive value")
    currency: str = "GHS"
    description: Optional[str] = None
    reference: Optional[str] = None
    metadata: Dict[str, Any] = Field(default_factory=dict)

    @field_validator("amount")
    @classmethod
    def validate_amount(cls, v: Decimal) -> Decimal:
        if v <= 0:
            raise ValueError("Amount must be positive")
        return v


class WithdrawalRequest(SusuBaseModel):
    customer_id: str
    amount: Decimal = Field(..., gt=0)
    currency: str = "GHS"
    description: Optional[str] = None
    reference: Optional[str] = None
    metadata: Dict[str, Any] = Field(default_factory=dict)


class TransferRequest(SusuBaseModel):
    from_customer_id: str
    to_customer_id: str
    amount: Decimal = Field(..., gt=0)
    currency: str = "GHS"
    description: Optional[str] = None
    reference: Optional[str] = None
    metadata: Dict[str, Any] = Field(default_factory=dict)

    @model_validator(mode="after")
    def validate_different_customers(self) -> "TransferRequest":
        if self.from_customer_id == self.to_customer_id:
            raise ValueError(
                "from_customer_id and to_customer_id must be different"
            )
        return self


class Transaction(SusuBaseModel):
    id: str
    customer_id: str
    type: TransactionType
    amount: Decimal
    currency: str
    status: TransactionStatus
    description: Optional[str] = None
    reference: Optional[str] = None
    metadata: Dict[str, Any] = Field(default_factory=dict)
    created_at: Optional[datetime] = None
    completed_at: Optional[datetime] = None


# ---------------------------------------------------------------------------
# Loan models
# ---------------------------------------------------------------------------


class Collateral(SusuBaseModel):
    type: CollateralType
    description: Optional[str] = None
    value: Decimal


class Guarantor(SusuBaseModel):
    name: str
    phone: str
    relationship: str
    email: Optional[str] = None


class LoanApplicationRequest(SusuBaseModel):
    customer_id: str
    amount: Decimal = Field(..., gt=0)
    currency: str = "GHS"
    purpose: str
    term: int = Field(..., gt=0, description="Loan term in months")
    interest_rate: Decimal = Field(..., gt=0)
    collateral: Optional[Collateral] = None
    guarantors: List[Guarantor] = Field(default_factory=list)
    metadata: Dict[str, Any] = Field(default_factory=dict)


class LoanApprovalRequest(SusuBaseModel):
    approved_amount: Decimal = Field(..., gt=0)
    approved_term: int = Field(..., gt=0)
    approved_rate: Decimal = Field(..., gt=0)
    conditions: List[str] = Field(default_factory=list)


class LoanDisbursementRequest(SusuBaseModel):
    disbursement_method: str
    account_details: Dict[str, Any] = Field(default_factory=dict)


class RepaymentRequest(SusuBaseModel):
    amount: Decimal = Field(..., gt=0)
    payment_date: date
    payment_method: str
    reference: Optional[str] = None


class LoanScheduleItem(SusuBaseModel):
    installment_number: int
    due_date: date
    principal: Decimal
    interest: Decimal
    total: Decimal
    outstanding_balance: Decimal
    status: str


class Loan(SusuBaseModel):
    id: str
    customer_id: str
    amount: Decimal
    currency: str
    term: int
    interest_rate: Decimal
    purpose: str
    status: LoanStatus
    disbursed_amount: Optional[Decimal] = None
    outstanding_balance: Optional[Decimal] = None
    collateral: Optional[Collateral] = None
    guarantors: List[Guarantor] = Field(default_factory=list)
    created_at: Optional[datetime] = None
    disbursed_at: Optional[datetime] = None


# ---------------------------------------------------------------------------
# Savings models
# ---------------------------------------------------------------------------


class SavingsAccountCreate(SusuBaseModel):
    customer_id: str
    account_type: SavingsAccountType = SavingsAccountType.REGULAR
    currency: str = "GHS"
    interest_rate: Optional[Decimal] = None
    minimum_balance: Decimal = Decimal("0.00")


class SavingsGoalCreate(SusuBaseModel):
    account_id: str
    name: str = Field(..., min_length=1, max_length=150)
    target_amount: Decimal = Field(..., gt=0)
    target_date: date
    monthly_contribution: Decimal = Field(..., gt=0)


class SavingsAccount(SusuBaseModel):
    id: str
    customer_id: str
    account_type: SavingsAccountType
    currency: str
    interest_rate: Optional[Decimal] = None
    minimum_balance: Decimal
    balance: Decimal = Decimal("0.00")
    status: str = "active"
    created_at: Optional[datetime] = None


class SavingsGoal(SusuBaseModel):
    id: str
    account_id: str
    name: str
    target_amount: Decimal
    current_amount: Decimal = Decimal("0.00")
    monthly_contribution: Decimal
    target_date: date
    status: str = "active"
    progress_percent: Optional[Decimal] = None
    created_at: Optional[datetime] = None


# ---------------------------------------------------------------------------
# Analytics models
# ---------------------------------------------------------------------------


class CustomerAnalytics(SusuBaseModel):
    customer_id: str
    total_deposits: Decimal
    total_withdrawals: Decimal
    total_loans: int
    active_loans: int
    savings_balance: Decimal
    transaction_count: int
    period_start: date
    period_end: date


class TransactionSummary(SusuBaseModel):
    period: str
    total_amount: Decimal
    transaction_count: int
    average_amount: Decimal
    currency: str


class AnalyticsReport(SusuBaseModel):
    id: str
    report_type: str
    format: str
    status: str
    download_url: Optional[str] = None
    created_at: Optional[datetime] = None
    expires_at: Optional[datetime] = None


# ---------------------------------------------------------------------------
# Pagination
# ---------------------------------------------------------------------------


class PagedResult(SusuBaseModel):
    """Generic paginated result returned by list endpoints."""

    data: List[Any] = Field(default_factory=list)
    total: int = 0
    page: int = 1
    limit: int = 50
    has_next: bool = False
    has_prev: bool = False

    @property
    def total_pages(self) -> int:
        if self.limit == 0:
            return 0
        import math
        return math.ceil(self.total / self.limit)


# ---------------------------------------------------------------------------
# Webhooks
# ---------------------------------------------------------------------------


class WebhookEvent(SusuBaseModel):
    id: str
    type: str
    created_at: datetime
    data: Dict[str, Any]
    api_version: str = "v1"
