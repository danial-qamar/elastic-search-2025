@forelse ($consumers as $consumer)
    <tr>
        <td>
            <div class="d-flex align-items-center gap-2">
                <div class="stat-icon violet" style="width: 32px; height: 32px; font-size: 14px; border-radius: 50%;">
                    <i class="bi bi-person"></i>
                </div>
                <span class="fw-semibold text-dark">{{ $consumer->name ?: '—' }}</span>
            </div>
        </td>
        <td>
            <span class="font-mono text-secondary">{{ $consumer->contactno ?: '—' }}</span>
        </td>
        <td>
            <span class="code-ref badge-violet px-2 py-1">{{ $consumer->reference_no ?: '—' }}</span>
        </td>
        <td>
            <span class="font-mono text-secondary">{{ $consumer->occupant_nicno ?: '—' }}</span>
        </td>
        <td class="text-end">
            <div class="d-inline-flex gap-2">
                <a href="{{ route('consumers.edit', $consumer->id) }}" class="btn btn-warning btn-sm" title="Edit Consumer">
                    <i class="bi bi-pencil-square"></i> Edit
                </a>
                <form action="{{ route('consumers.destroy', $consumer->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this consumer?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm" title="Delete Consumer">
                        <i class="bi bi-trash3-fill"></i> Delete
                    </button>
                </form>
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="5" class="text-center py-5">
            <div class="py-4">
                <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
                <h5 class="text-dark fw-bold mb-1">No Consumers Found</h5>
                <p class="text-muted small">No consumer records found in the database.</p>
            </div>
        </td>
    </tr>
@endforelse
