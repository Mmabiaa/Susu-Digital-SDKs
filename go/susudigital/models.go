package susudigital

// PagedResult represents a paginated API response.
type PagedResult[T any] struct {
	Data       []T    `json:"data"`
	HasMore    bool   `json:"has_more"`
	TotalCount int    `json:"total_count"`
	NextCursor string `json:"next_cursor,omitempty"`
}

// BaseModel contains standard fields.
type BaseModel struct {
	ID        string `json:"id"`
	CreatedAt string `json:"created_at"`
	UpdatedAt string `json:"updated_at"`
}
