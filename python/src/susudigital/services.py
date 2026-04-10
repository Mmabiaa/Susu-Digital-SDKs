"""Service classes for the Susu Digital Python SDK (synchronous)."""

from __future__ import annotations

from typing import Any, Dict, List, Optional, Union

from susudigital._http import HttpClient
from susudigital.types import (
    Balance,
    CollateralType,
    Customer,
    CustomerCreate,
    CustomerStatus,
    CustomerUpdate,
    DepositRequest,
    Loan,
    LoanApplicationRequest,
    LoanApprovalRequest,
    LoanDisbursementRequest,
    LoanScheduleItem,
    LoanStatus,
    PagedResult,
    RepaymentRequest,
    SavingsAccount,
    SavingsAccountCreate,
    SavingsGoal,
    SavingsGoalCreate,
    Transaction,
    TransactionStatus,
    TransactionType,
    TransferRequest,
    WithdrawalRequest,
    CustomerAnalytics,
    AnalyticsReport,
    TransactionSummary,
    WebhookEvent,
)


def _clean(data: Dict[str, Any]) -> Dict[str, Any]:
    """Remove keys whose value is None – keeps API payloads minimal."""
    return {k: v for k, v in data.items() if v is not None}


# ---------------------------------------------------------------------------
# Customer Service
# ---------------------------------------------------------------------------


class CustomerService:
    """Manage Susu Digital customers.

    .. code-block:: python

        customer = client.customers.create(
            CustomerCreate(
                first_name="John",
                last_name="Doe",
                phone="+233XXXXXXXXX",
            )
        )
    """

    _path = "/customers"

    def __init__(self, http: HttpClient) -> None:
        self._http = http

    def create(self, data: Union[CustomerCreate, Dict[str, Any]]) -> Customer:
        """Create a new customer.

        Args:
            data: A :class:`~susudigital.types.CustomerCreate` instance or a
                plain dict with the same keys.

        Returns:
            The newly created :class:`~susudigital.types.Customer`.
        """
        if isinstance(data, dict):
            data = CustomerCreate(**data)
        payload = data.to_dict()
        response = self._http.post(self._path, json=payload)
        return Customer(**response)

    def get(self, customer_id: str) -> Customer:
        """Retrieve a customer by their ID."""
        response = self._http.get(f"{self._path}/{customer_id}")
        return Customer(**response)

    def update(
        self,
        customer_id: str,
        data: Optional[Union[CustomerUpdate, Dict[str, Any]]] = None,
        **kwargs: Any,
    ) -> Customer:
        """Update customer fields.

        You may pass a :class:`~susudigital.types.CustomerUpdate` object, a
        plain dict, **or** individual keyword arguments::

            client.customers.update(cust_id, email="new@example.com")
        """
        if data is None:
            data = kwargs
        if isinstance(data, dict):
            data = CustomerUpdate(**data)
        payload = data.to_dict()
        response = self._http.patch(f"{self._path}/{customer_id}", json=payload)
        return Customer(**response)

    def delete(self, customer_id: str) -> None:
        """Delete (deactivate) a customer record."""
        self._http.delete(f"{self._path}/{customer_id}")

    def get_balance(self, customer_id: str) -> Balance:
        """Retrieve a customer's current balance."""
        response = self._http.get(f"{self._path}/{customer_id}/balance")
        return Balance(**response)

    def list(
        self,
        *,
        page: int = 1,
        limit: int = 50,
        search: Optional[str] = None,
        status: Optional[Union[CustomerStatus, str]] = None,
    ) -> PagedResult:
        """List customers with optional filtering and pagination."""
        params: Dict[str, Any] = {"page": page, "limit": limit}
        if search:
            params["search"] = search
        if status:
            params["status"] = status.value if isinstance(status, CustomerStatus) else status
        response = self._http.get(self._path, params=params)
        data = [Customer(**c) for c in response.get("data", [])]
        return PagedResult(
            data=data,
            total=response.get("total", len(data)),
            page=response.get("page", page),
            limit=response.get("limit", limit),
            has_next=response.get("has_next", False),
            has_prev=response.get("has_prev", False),
        )


# ---------------------------------------------------------------------------
# Transaction Service
# ---------------------------------------------------------------------------


class TransactionService:
    """Process payments and retrieve transaction history.

    .. code-block:: python

        txn = client.transactions.deposit(
            DepositRequest(
                customer_id="cust_123",
                amount=Decimal("100.00"),
                currency="GHS",
            )
        )
    """

    _path = "/transactions"

    def __init__(self, http: HttpClient) -> None:
        self._http = http

    def deposit(self, data: Union[DepositRequest, Dict[str, Any]]) -> Transaction:
        """Create a deposit transaction."""
        if isinstance(data, dict):
            data = DepositRequest(**data)
        payload = data.to_dict()
        payload["type"] = TransactionType.DEPOSIT.value
        response = self._http.post(self._path, json=payload)
        return Transaction(**response)

    def withdraw(self, data: Union[WithdrawalRequest, Dict[str, Any]]) -> Transaction:
        """Create a withdrawal transaction."""
        if isinstance(data, dict):
            data = WithdrawalRequest(**data)
        payload = data.to_dict()
        payload["type"] = TransactionType.WITHDRAWAL.value
        response = self._http.post(self._path, json=payload)
        return Transaction(**response)

    def transfer(self, data: Union[TransferRequest, Dict[str, Any]]) -> Transaction:
        """Create a peer-to-peer transfer."""
        if isinstance(data, dict):
            data = TransferRequest(**data)
        payload = data.to_dict()
        payload["type"] = TransactionType.TRANSFER.value
        response = self._http.post(f"{self._path}/transfer", json=payload)
        return Transaction(**response)

    def get(self, transaction_id: str) -> Transaction:
        """Retrieve a transaction by its ID."""
        response = self._http.get(f"{self._path}/{transaction_id}")
        return Transaction(**response)

    def list(
        self,
        *,
        customer_id: Optional[str] = None,
        start_date: Optional[Any] = None,
        end_date: Optional[Any] = None,
        transaction_type: Optional[Union[TransactionType, str]] = None,
        status: Optional[Union[TransactionStatus, str]] = None,
        page: int = 1,
        limit: int = 50,
    ) -> PagedResult:
        """List transactions with optional filters."""
        params: Dict[str, Any] = {"page": page, "limit": limit}
        if customer_id:
            params["customer_id"] = customer_id
        if start_date:
            params["start_date"] = str(start_date)
        if end_date:
            params["end_date"] = str(end_date)
        if transaction_type:
            params["type"] = (
                transaction_type.value
                if isinstance(transaction_type, TransactionType)
                else transaction_type
            )
        if status:
            params["status"] = (
                status.value if isinstance(status, TransactionStatus) else status
            )
        response = self._http.get(self._path, params=params)
        data = [Transaction(**t) for t in response.get("data", [])]
        return PagedResult(
            data=data,
            total=response.get("total", len(data)),
            page=response.get("page", page),
            limit=response.get("limit", limit),
            has_next=response.get("has_next", False),
            has_prev=response.get("has_prev", False),
        )


# ---------------------------------------------------------------------------
# Loan Service
# ---------------------------------------------------------------------------


class LoanService:
    """Loan origination and servicing.

    .. code-block:: python

        application = client.loans.create_application(
            LoanApplicationRequest(
                customer_id="cust_123",
                amount=Decimal("5000.00"),
                term=12,
                interest_rate=Decimal("15.0"),
                purpose="business_expansion",
            )
        )
    """

    _path = "/loans"

    def __init__(self, http: HttpClient) -> None:
        self._http = http

    def create_application(
        self, data: Union[LoanApplicationRequest, Dict[str, Any]]
    ) -> Loan:
        """Submit a new loan application."""
        if isinstance(data, dict):
            data = LoanApplicationRequest(**data)
        response = self._http.post(f"{self._path}/applications", json=data.to_dict())
        return Loan(**response)

    def approve(
        self,
        loan_id: str,
        data: Union[LoanApprovalRequest, Dict[str, Any]],
    ) -> Loan:
        """Approve a loan application with negotiated terms."""
        if isinstance(data, dict):
            data = LoanApprovalRequest(**data)
        response = self._http.post(
            f"{self._path}/{loan_id}/approve", json=data.to_dict()
        )
        return Loan(**response)

    def disburse(
        self,
        loan_id: str,
        data: Union[LoanDisbursementRequest, Dict[str, Any]],
    ) -> Loan:
        """Disburse an approved loan."""
        if isinstance(data, dict):
            data = LoanDisbursementRequest(**data)
        response = self._http.post(
            f"{self._path}/{loan_id}/disburse", json=data.to_dict()
        )
        return Loan(**response)

    def record_repayment(
        self, loan_id: str, data: Union[RepaymentRequest, Dict[str, Any]]
    ) -> Dict[str, Any]:
        """Record a repayment against a loan."""
        if isinstance(data, dict):
            data = RepaymentRequest(**data)
        return self._http.post(
            f"{self._path}/{loan_id}/repayments", json=data.to_dict()
        )

    def get(self, loan_id: str) -> Loan:
        """Retrieve loan details by ID."""
        response = self._http.get(f"{self._path}/{loan_id}")
        return Loan(**response)

    def get_schedule(self, loan_id: str) -> List[LoanScheduleItem]:
        """Retrieve the full repayment schedule for a loan."""
        response = self._http.get(f"{self._path}/{loan_id}/schedule")
        return [LoanScheduleItem(**item) for item in response.get("data", [])]

    def list(
        self,
        *,
        customer_id: Optional[str] = None,
        status: Optional[Union[LoanStatus, str]] = None,
        page: int = 1,
        limit: int = 20,
    ) -> PagedResult:
        """List loans with optional filters."""
        params: Dict[str, Any] = {"page": page, "limit": limit}
        if customer_id:
            params["customer_id"] = customer_id
        if status:
            params["status"] = status.value if isinstance(status, LoanStatus) else status
        response = self._http.get(self._path, params=params)
        data = [Loan(**loan) for loan in response.get("data", [])]
        return PagedResult(
            data=data,
            total=response.get("total", len(data)),
            page=response.get("page", page),
            limit=response.get("limit", limit),
            has_next=response.get("has_next", False),
            has_prev=response.get("has_prev", False),
        )


# ---------------------------------------------------------------------------
# Savings Service
# ---------------------------------------------------------------------------


class SavingsService:
    """Savings account management."""

    _path = "/savings"

    def __init__(self, http: HttpClient) -> None:
        self._http = http

    def create_account(
        self, data: Union[SavingsAccountCreate, Dict[str, Any]]
    ) -> SavingsAccount:
        """Open a new savings account for a customer."""
        if isinstance(data, dict):
            data = SavingsAccountCreate(**data)
        response = self._http.post(f"{self._path}/accounts", json=data.to_dict())
        return SavingsAccount(**response)

    def get_account(self, account_id: str) -> SavingsAccount:
        """Retrieve savings account details."""
        response = self._http.get(f"{self._path}/accounts/{account_id}")
        return SavingsAccount(**response)

    def get_balance(self, account_id: str) -> Balance:
        """Retrieve the balance for a savings account."""
        response = self._http.get(f"{self._path}/accounts/{account_id}/balance")
        return Balance(**response)

    def create_goal(self, data: Union[SavingsGoalCreate, Dict[str, Any]]) -> SavingsGoal:
        """Create a savings goal linked to an account."""
        if isinstance(data, dict):
            data = SavingsGoalCreate(**data)
        response = self._http.post(f"{self._path}/goals", json=data.to_dict())
        return SavingsGoal(**response)

    def get_goal(self, goal_id: str) -> SavingsGoal:
        """Retrieve a savings goal."""
        response = self._http.get(f"{self._path}/goals/{goal_id}")
        return SavingsGoal(**response)

    def list_accounts(
        self,
        customer_id: Optional[str] = None,
        page: int = 1,
        limit: int = 20,
    ) -> PagedResult:
        """List savings accounts."""
        params: Dict[str, Any] = {"page": page, "limit": limit}
        if customer_id:
            params["customer_id"] = customer_id
        response = self._http.get(f"{self._path}/accounts", params=params)
        data = [SavingsAccount(**a) for a in response.get("data", [])]
        return PagedResult(
            data=data,
            total=response.get("total", len(data)),
            page=page,
            limit=limit,
            has_next=response.get("has_next", False),
            has_prev=response.get("has_prev", False),
        )


# ---------------------------------------------------------------------------
# Analytics Service
# ---------------------------------------------------------------------------


class AnalyticsService:
    """Business intelligence and reporting."""

    _path = "/analytics"

    def __init__(self, http: HttpClient) -> None:
        self._http = http

    def get_customer_analytics(
        self,
        customer_id: str,
        start_date: Any,
        end_date: Any,
    ) -> CustomerAnalytics:
        """Retrieve analytics for a specific customer over a period."""
        params = {
            "start_date": str(start_date),
            "end_date": str(end_date),
        }
        response = self._http.get(
            f"{self._path}/customers/{customer_id}", params=params
        )
        return CustomerAnalytics(**response)

    def get_transaction_summary(
        self,
        start_date: Any,
        end_date: Any,
        group_by: str = "month",
    ) -> List[TransactionSummary]:
        """Retrieve aggregated transaction summaries."""
        params = {
            "start_date": str(start_date),
            "end_date": str(end_date),
            "group_by": group_by,
        }
        response = self._http.get(f"{self._path}/transactions", params=params)
        return [TransactionSummary(**item) for item in response.get("data", [])]

    def generate_report(
        self,
        report_type: str,
        start_date: Any,
        end_date: Any,
        *,
        format: str = "json",
        filters: Optional[Dict[str, Any]] = None,
    ) -> AnalyticsReport:
        """Request generation of an analytics report."""
        payload: Dict[str, Any] = {
            "report_type": report_type,
            "start_date": str(start_date),
            "end_date": str(end_date),
            "format": format,
        }
        if filters:
            payload["filters"] = filters
        response = self._http.post(f"{self._path}/reports", json=payload)
        return AnalyticsReport(**response)
