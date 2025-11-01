{{-- resources/views/ai-images/index.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8 mx-auto">
            {{-- Форма генерации --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Генератор ИИ изображений</h5>
                </div>

                <div class="card-body">
                    <form id="aiImageForm">
                        @csrf
                        <div class="mb-3">
                            <label for="prompt" class="form-label fw-semibold">Описание изображения:</label>
                            <textarea 
                                class="form-control" 
                                id="prompt" 
                                name="prompt" 
                                rows="4" 
                                placeholder="Опишите детально что вы хотите увидеть..."
                                required
                                maxlength="1000"
                            ></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg w-100" id="generateBtn">
                            Сгенерировать изображение
                        </button>
                    </form>

                    <div id="loading" class="text-center py-4" style="display: none;">
                        <div class="spinner-border text-primary mb-2" role="status">
                            <span class="visually-hidden">Загрузка...</span>
                        </div>
                        <div class="fw-semibold">Генерация изображения...</div>
                    </div>

                    <div id="resultAlert" class="alert" style="display: none;"></div>
                </div>
            </div>

            {{-- Блок истории --}}
			<div class="card shadow-sm">
				<div class="card-header bg-secondary text-white">
					<h5 class="mb-0">История генераций</h5>
				</div>
				<div class="card-body">
					@if($images->count() > 0)
						<div class="row">
							@foreach($images as $image)
								<div class="col-md-6 mb-3">
									<div class="card h-100">
										<img src="{{ Storage::url($image->image_path) }}" 
											class="card-img-top" 
											alt="{{ $image->prompt }}"
											style="height: 200px; object-fit: cover;">
										<div class="card-body">
											<p class="card-text">{{ Str::limit($image->prompt, 100) }}</p>
											<small class="text-muted">
												{{ $image->created_at->format('d.m.Y H:i') }}
											</small>
										</div>
									</div>
								</div>
							@endforeach
						</div>
					@else
						<div class="text-center py-5 text-muted">
							<p>История генераций пуста</p>
							<p>Сгенерируйте первое изображение!</p>
						</div>
					@endif
				</div>
			</div>
        </div>
    </div>
</div>

<script>
document.getElementById('aiImageForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const form = e.target;
    const formData = new FormData(form);
    const loading = document.getElementById('loading');
    const generateBtn = document.getElementById('generateBtn');
    const resultAlert = document.getElementById('resultAlert');
    
    // Блокируем кнопку и показываем загрузку
    generateBtn.disabled = true;
    loading.style.display = 'block';
    resultAlert.style.display = 'none';
    
    fetch('{{ route("ai-images.generate") }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        loading.style.display = 'none';
        generateBtn.disabled = false;
        
        if (data.success) {
            resultAlert.className = 'alert alert-success';
            resultAlert.innerHTML = 'Изображение успешно сгенерировано! Страница будет перезагружена...';
            resultAlert.style.display = 'block';
            form.reset();
            
            // Перезагружаем страницу через 2 секунды чтобы показать новое изображение в истории
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        } else {
            resultAlert.className = 'alert alert-danger';
            resultAlert.innerHTML = 'Ошибка: ' + (data.message || 'Произошла ошибка при генерации');
            resultAlert.style.display = 'block';
        }
    })
    .catch(error => {
        loading.style.display = 'none';
        generateBtn.disabled = false;
        resultAlert.className = 'alert alert-danger';
        resultAlert.innerHTML = 'Ошибка сети или сервера';
        resultAlert.style.display = 'block';
        console.error('Error:', error);
    });
});
</script>
@endsection