<form action="{{ route('delivery.cost') }}" method="POST">
    @csrf

    <div class="form-group">
        <label for="cost">Cost</label>
        <input
            type="number"
            name="cost"
            id="cost"
            value="{{ $delivery->cost }}"
            class="form-control @error('cost') is-invalid @enderror"
            required
        >
        @error('cost')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <button type="submit" class="btn btn-primary">Submit</button>
</form>
