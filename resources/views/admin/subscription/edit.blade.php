@extends('admin.layout.app')
@section('title', 'Edit Subscription Plan')
@section('content')
    <div class="main-content">
        <section class="section">
            <div class="section-body">
                <a class="btn btn-primary mb-3" href="{{ route('subscription.index') }}">Back</a>
                <form id="edit_subscription_plan" action="{{ route('subscription.update', $plan->id) }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-12 col-md-12 col-lg-12">
                            <div class="card">
                                <h4 class="text-center my-4">Edit Subscription Plan</h4>
                                <div class="row mx-0 px-4">

                                    <!-- Plan Name -->
                                    <div class="col-sm-6 pl-sm-0 pr-sm-3">
                                        <div class="form-group">
                                            <label for="name">Plan Name <span style="color: red;">*</span></label>
                                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                                id="name" name="name" value="{{ old('name', $plan->name) }}"
                                                placeholder="Enter plan name (e.g. Free, Basic, Standard)"
                                                {{ $plan->id == 2 ? 'disabled' : '' }}>
                                            @error('name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <!-- Price -->
                                    <div class="col-sm-6 pl-sm-0 pr-sm-3">
                                        <div class="form-group">
                                            <label for="price">Price<span style="color: red;">*</span></label>
                                            <input type="number" step="0.01"
                                                class="form-control @error('price') is-invalid @enderror" id="price"
                                                name="price" value="{{ old('price', intval($plan->price)) }}"
                                                placeholder="Enter price (0 for Free)"
                                                {{ $plan->id == 2 ? 'disabled' : '' }}>
                                            @error('price')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <!-- Duration Days -->
                                    <div class="col-sm-6 pl-sm-0 pr-sm-3">
                                        <div class="form-group">
                                            <label for="duration_days">Duration (Days) <span style="color: red;">*</span></label>
                                            <input type="number"
                                                class="form-control @error('duration_days') is-invalid @enderror"
                                                id="duration_days" name="duration_days"
                                                value="{{ old('duration_days', $plan->duration_days) }}"
                                                placeholder="Enter duration in days" readonly>
                                            @error('duration_days')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <!-- Product Limit -->
                                    <div class="col-sm-6 pl-sm-0 pr-sm-3">
                                        <div class="form-group">
                                            <label for="product_limit">Product Limit <span style="color: red;">*</span></label>
                                            <input type="number"
                                                class="form-control @error('product_limit') is-invalid @enderror"
                                                id="product_limit" name="product_limit"
                                                value="{{ old('product_limit', $plan->product_limit) }}"
                                                placeholder="Enter product limit">
                                            @error('product_limit')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <!-- Description -->
                                    <div class="col-sm-6 pl-sm-0 pr-sm-3">
                                        <div class="form-group">
                                            <label for="description">Description</label>
                                            <textarea class="form-control @error('description') is-invalid @enderror"
                                                id="description" name="description" rows="3"
                                                placeholder="Enter plan description">{{ old('description', $plan->description) }}</textarea>
                                            @error('description')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <!-- Status -->
                                    <div class="col-sm-6 pl-sm-0 pr-sm-3">
                                        <div class="form-group">
                                            <label for="is_active">Status</label>
                                            <select class="form-control @error('is_active') is-invalid @enderror"
                                                id="is_active" name="is_active" {{ $plan->id == 2 ? 'disabled' : '' }}>
                                                <option value="1" {{ old('is_active', $plan->is_active) == 1 ? 'selected' : '' }}>Active</option>
                                                <option value="0" {{ old('is_active', $plan->is_active) == 0 ? 'selected' : '' }}>Inactive</option>
                                            </select>
                                            @error('is_active')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <!-- Submit Button -->
                                    <div class="card-footer text-center row">
                                        <div class="col-12">
                                            <button type="submit" class="btn btn-primary mr-1 btn-bg"
                                                id="submit">Update</button>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </section>
    </div>
@endsection
