"""
Async service layer for the Susu Digital Python SDK.

Each class here is the ``async / await`` counterpart of the synchronous
service in :mod:`susudigital.services`.  All methods have identical
signatures but return ``Awaitable`` results.
"""

from __future__ import annotations

from typing import Any, Dict, List, Optional, Union

from susudigital._http import AsyncHttpClient
from susudigital.types import (
    AnalyticsReport,
    Balance,
    Customer,
    CustomerAnalytics,
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
    TransactionSummary,
    TransactionType,
    TransferRequest,
    WithdrawalRequest,
)


# ---------------------------------------------------------------------------
# Async Customer Service
# ---------------------------------------------------------------------------


class AsyncCustomerService:
    """Async customer management – mirrors :class:`~susudigital.services.CustomerService`."""

    _path = "/customers"

    def __init__(self, http: AsyncHttpClient) -> None:
        self._http = http

    async def create(self, data: Union[CustomerCreate, Dict[str, Any]]) -> Customer:
        if isinstance(data, dict):
            data = CustomerCreate(**data)
        response = await self._http.post(self._path, json=data.to_dict())
        return Customer(**response)

    async def get(self, customer_id: str) -> Customer:
        response = await self._http.get(f"{self._path}/{customer_id}")
        return Customer(**response)

    async def update(
        self,
        customer_id: str,
        data: Optional[Union[CustomerUpdate, Dict[str, Any]]] = None,
        **kwargs: Any,
    ) -> Customer:
        if data is None:
            data = kwargs
        if isinstance(data, dict):
            data = CustomerUpdate(**data)
        response = await self._http.patch(
            f"{self._path}/{customer_id}", json=data.to_dict()
        )
        return Customer(**response)

    async def delete(self, customer_id: str) -> None:
        await self._http.delete(f"{self._path}/{customer_id}")

    async def get_balance(self, customer_id: str) -> Balance:
        response = await self._http.get(f"{self._path}/{customer_id}/balance")
        return Balance(**response)

    async def list(
        self,
        *,
        page: int = 1,
        limit: int = 50,
        search: Optional[str] = None,
        status: Optional[Union[CustomerStatus, str]] = None,
    ) -> PagedResult:
        params: Dict[str, Any] = {"page": page, "limit": limit}
        if search:
            params["search"] = search
        if status:
            params["status"] = (
                status.value if isinstance(status, CustomerStatus) else status
            )
        response = await self._http.get(self._path, params=params)
        data = [Customer(**c) for c in response.get("data", [])]
        return PagedResult(
            data=data,
            total=response.get("total", len(data)),
            page=page,
            limit=limit,
            has_next=response.get("has_next", False),
            has_prev=response.get("has_prev", False),
        )


# ---------------------------------------------------------------------------
# Async Transaction Service
# ---------------------------------------------------------------------------


class AsyncTransactionService:
    """Async transaction processing."""

    _path = "/transactions"

    def __init__(self, http: AsyncHttpClient) -> None:
        self._http = http

    async def deposit(self, data: Union[DepositRequest, Dict[str, Any]]) -> Transaction:
        if isinstance(data, dict):
            data = DepositRequest(**data)
        payload = data.to_dict()
        payload["type"] = TransactionType.DEPOSIT.value
        response = await self._http.post(self._path, json=payload)
        return Transaction(**response)

    async def withdraw(
        self, data: Union[WithdrawalRequest, Dict[str, Any]]
    ) -> Transaction:
        if isinstance(data, dict):
            data = WithdrawalRequest(**data)
        payload = data.to_dict()
        payload["type"] = TransactionType.WITHDRAWAL.value
        response = await self._http.post(self._path, json=payload)
        return Transaction(**response)

    async def transfer(
        self, data: Union[TransferRequest, Dict[str, Any]]
    ) -> Transaction:
        if isinstance(data, dict):
            data = TransferRequest(**data)
        payload = data.to_dict()
        payload["type"] = TransactionType.TRANSFER.value
        response = await self._http.post(f"{self._path}/transfer", json=payload)
        return Transaction(**response)

    async def get(self, transaction_id: str) -> Transaction:
        response = await self._http.get(f"{self._path}/{transaction_id}")
        return Transaction(**response)

    async def list(
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
        response = await self._http.get(self._path, params=params)
        data = [Transaction(**t) for t in response.get("data", [])]
        return PagedResult(
            data=data,
            total=response.get("total", len(data)),
            page=page,
            limit=limit,
            has_next=response.get("has_next", False),
            has_prev=response.get("has_prev", False),
        )


# ---------------------------------------------------------------------------
# Async Loan Service
# ---------------------------------------------------------------------------


class AsyncLoanService:
    """Async loan origination and servicing."""

    _path = "/loans"

    def __init__(self, http: AsyncHttpClient) -> None:
        self._http = http

    async def create_application(
        self, data: Union[LoanApplicationRequest, Dict[str, Any]]
    ) -> Loan:
        if isinstance(data, dict):
            data = LoanApplicationRequest(**data)
        response = await self._http.post(
            f"{self._path}/applications", json=data.to_dict()
        )
        return Loan(**response)

    async def approve(
        self, loan_id: str, data: Union[LoanApprovalRequest, Dict[str, Any]]
    ) -> Loan:
        if isinstance(data, dict):
            data = LoanApprovalRequest(**data)
        response = await self._http.post(
            f"{self._path}/{loan_id}/approve", json=data.to_dict()
        )
        return Loan(**response)

    async def disburse(
        self, loan_id: str, data: Union[LoanDisbursementRequest, Dict[str, Any]]
    ) -> Loan:
        if isinstance(data, dict):
            data = LoanDisbursementRequest(**data)
        response = await self._http.post(
            f"{self._path}/{loan_id}/disburse", json=data.to_dict()
        )
        return Loan(**response)

    async def record_repayment(
        self, loan_id: str, data: Union[RepaymentRequest, Dict[str, Any]]
    ) -> Dict[str, Any]:
        if isinstance(data, dict):
            data = RepaymentRequest(**data)
        return await self._http.post(
            f"{self._path}/{loan_id}/repayments", json=data.to_dict()
        )

    async def get(self, loan_id: str) -> Loan:
        response = await self._http.get(f"{self._path}/{loan_id}")
        return Loan(**response)

    async def get_schedule(self, loan_id: str) -> List[LoanScheduleItem]:
        response = await self._http.get(f"{self._path}/{loan_id}/schedule")
        return [LoanScheduleItem(**item) for item in response.get("data", [])]

    async def list(
        self,
        *,
        customer_id: Optional[str] = None,
        status: Optional[Union[LoanStatus, str]] = None,
        page: int = 1,
        limit: int = 20,
    ) -> PagedResult:
        params: Dict[str, Any] = {"page": page, "limit": limit}
        if customer_id:
            params["customer_id"] = customer_id
        if status:
            params["status"] = (
                status.value if isinstance(status, LoanStatus) else status
            )
        response = await self._http.get(self._path, params=params)
        data = [Loan(**loan) for loan in response.get("data", [])]
        return PagedResult(
            data=data,
            total=response.get("total", len(data)),
            page=page,
            limit=limit,
            has_next=response.get("has_next", False),
            has_prev=response.get("has_prev", False),
        )


# ---------------------------------------------------------------------------
# Async Savings Service
# ---------------------------------------------------------------------------


class AsyncSavingsService:
    """Async savings account management."""

    _path = "/savings"

    def __init__(self, http: AsyncHttpClient) -> None:
        self._http = http

    async def create_account(
        self, data: Union[SavingsAccountCreate, Dict[str, Any]]
    ) -> SavingsAccount:
        if isinstance(data, dict):
            data = SavingsAccountCreate(**data)
        response = await self._http.post(
            f"{self._path}/accounts", json=data.to_dict()
        )
        return SavingsAccount(**response)

    async def get_account(self, account_id: str) -> SavingsAccount:
        response = await self._http.get(f"{self._path}/accounts/{account_id}")
        return SavingsAccount(**response)

    async def get_balance(self, account_id: str) -> Balance:
        response = await self._http.get(
            f"{self._path}/accounts/{account_id}/balance"
        )
        return Balance(**response)

    async def create_goal(
        self, data: Union[SavingsGoalCreate, Dict[str, Any]]
    ) -> SavingsGoal:
        if isinstance(data, dict):
            data = SavingsGoalCreate(**data)
        response = await self._http.post(f"{self._path}/goals", json=data.to_dict())
        return SavingsGoal(**response)

    async def get_goal(self, goal_id: str) -> SavingsGoal:
        response = await self._http.get(f"{self._path}/goals/{goal_id}")
        return SavingsGoal(**response)


# ---------------------------------------------------------------------------
# Async Analytics Service
# ---------------------------------------------------------------------------


class AsyncAnalyticsService:
    """Async analytics and reporting."""

    _path = "/analytics"

    def __init__(self, http: AsyncHttpClient) -> None:
        self._http = http

    async def get_customer_analytics(
        self, customer_id: str, start_date: Any, end_date: Any
    ) -> CustomerAnalytics:
        params = {"start_date": str(start_date), "end_date": str(end_date)}
        response = await self._http.get(
            f"{self._path}/customers/{customer_id}", params=params
        )
        return CustomerAnalytics(**response)

    async def get_transaction_summary(
        self, start_date: Any, end_date: Any, group_by: str = "month"
    ) -> List[TransactionSummary]:
        params = {
            "start_date": str(start_date),
            "end_date": str(end_date),
            "group_by": group_by,
        }
        response = await self._http.get(f"{self._path}/transactions", params=params)
        return [TransactionSummary(**item) for item in response.get("data", [])]

    async def generate_report(
        self,
        report_type: str,
        start_date: Any,
        end_date: Any,
        *,
        format: str = "json",
        filters: Optional[Dict[str, Any]] = None,
    ) -> AnalyticsReport:
        payload: Dict[str, Any] = {
            "report_type": report_type,
            "start_date": str(start_date),
            "end_date": str(end_date),
            "format": format,
        }
        if filters:
            payload["filters"] = filters
        response = await self._http.post(f"{self._path}/reports", json=payload)
        return AnalyticsReport(**response)
