<template>
    <DefaultField
        :field="currentField"
        :errors="errors"
        :full-width-content="true"
        :show-help-text="!isReadonly"
    >
        <template #field>
            <div
                v-if="hasValue"
                class="mt-4"
            >
                <div
                    v-if="videoUrl"
                    class="video-wrapper inline-flex box-content border border-gray-200 dark:border-gray-700 rounded-lg divide-x divide-gray-200 dark:divide-gray-700 overflow-hidden">
                    <div>
                        <video
                            controls
                            controlsList="nodownload"
                            :src="videoUrl"
                            :autoplay="false"
                            class="flex-grow"
                            :poster="imageUrl"
                        />
                    </div>
                    <div class="flex-none flex flex-col divide-y divide-gray-200 dark:divide-gray-700">
                        <a
                            v-if="shouldShowRemoveButton"
                            :dusk="currentField.attribute + '-delete-link'"
                            type="button"
                            class="cursor-pointer group-control flex items-center justify-center w-8 h-8"
                            :title="__('Delete')"
                            @click.prevent="confirmRemoval"
                        >
                            <Icon
                                type="trash"
                                width="16"
                                height="16"
                            />
                        </a>
                        <a
                            :href="videoUrl"
                            target="_blank"
                            :dusk="currentField.attribute + '-open-link'"
                            type="button"
                            class="cursor-pointer group-control flex items-center justify-center w-8 h-8"
                            :title="__('Open')"
                        >
                            <Icon
                                type="external-link"
                                width="16"
                                height="16"
                            />
                        </a>
                        <a v-if="currentField.downloadable"
                           target="_blank"
                           :dusk="currentField.attribute + '-download-link'"
                           type="button"
                           class="cursor-pointer group-control flex items-center justify-center w-8 h-8"
                           :title="__('Download')"
                           @keydown.enter.prevent="download"
                           @click.prevent="download"
                        >
                            <Icon
                                type="download"
                                width="16"
                                height="16"
                            />
                        </a>
                    </div>
                </div>
                <ConfirmUploadRemovalModal
                    :show="removeModalOpen"
                    @confirm="removeFile"
                    @close="closeRemoveModal"
                />
            </div>

            <p
                v-if="!hasValue && isReadonly"
                class="pt-2 text-sm text-90"
            >
                {{ __('This file field is read-only.') }}
            </p>

            <div
                v-if="shouldShowField"
                class="form-file mr-4"
                :class="{ 'opacity-75': isReadonly }"
            >
                <div>
                    <input
                        :id="idAttr"
                        ref="fileField"
                        :dusk="currentField.attribute"
                        class="form-file-input select-none"
                        type="file"
                        name="name"
                        :disabled="isReadonly || uploading"
                        :accept="currentField.acceptedTypes"
                        @change="fileChange"
                    >

                    <label
                        :for="labelFor"
                        class="cursor-pointer focus:outline-none focus:ring rounded border-2 border-primary-300 dark:border-gray-500 hover:border-primary-500 active:border-primary-400 dark:hover:border-gray-400 dark:active:border-gray-300 bg-white dark:bg-transparent text-primary-500 dark:text-gray-400 px-3 h-9 inline-flex items-center font-bold flex-shrink-0"
                    >
            <span
                v-if="uploading"
            >{{ __('Uploading') }} ({{ uploadProgress }}%)</span>
                        <span v-else>{{ __('Choose File') }}</span>
                    </label>

                    <span
                        v-if="shouldShowField"
                        class="ml-2 text-sm select-none"
                    >{{ currentLabel }}</span>
                    <p
                        v-if="hasError"
                        class="text-xs mt-2 text-red-500"
                    >
                        {{ firstError }}
                    </p>
                </div>

                <div
                    v-if="laruploadIsOn && fileName"
                    class="mt-4"
                >
                    <input
                        :id="idAttr + '-thumbnail'"
                        ref="thumbnailField"
                        :dusk="currentField.attribute + '_image'"
                        class="form-file-input select-none"
                        type="file"
                        name="thumbnail"
                        :disabled="isReadonly || thumbnailUploading"
                        accept="image/jpeg,image/png"
                        @change="thumbnailFileChange"
                    >

                    <label
                        :for="labelFor + '-thumbnail'"
                        class="form-file-btn btn btn-default btn-primary select-none"
                    >
                        <span v-if="thumbnailUploading">{{ __('Uploading') }} ({{ thumbnailUploadProgress }}%)</span>
                        <span v-else>{{ __('Choose Cover') }}</span>
                    </label>

                    <span
                        v-if="shouldShowField"
                        class="text-90 text-sm select-none"
                    >{{ currentThumbnailLabel }}</span>
                    <p
                        v-if="hasError"
                        class="text-xs mt-2 text-red-500"
                    >
                        {{ firstError }}
                    </p>
                </div>
            </div>
        </template>
    </DefaultField>
</template>

<script>
import download from "../functions/download";
import Vapor from 'laravel-vapor';
import {DependentFormField, HandlesValidationErrors, Errors} from 'laravel-nova';

export default {
    mixins: [HandlesValidationErrors, DependentFormField],
    props: ['resourceId', 'relatedResourceName', 'relatedResourceId', 'viaRelationship'],
    data: () => ({
        file: null,
        thumbnailFile: null,
        fileName: '',
        thumbnailFileName: '',
        removeModalOpen: false,
        missing: false,
        deleted: false,
        src: null,
        uploadErrors: new Errors(),
        vaporFile: {
            key: '',
            uuid: '',
            filename: '',
            extension: '',
        },
        uploading: false,
        thumbnailUploading: false,
        uploadProgress: 0,
        thumbnailUploadProgress: 0,
    }),
    computed: {
        /**
         * Determine if the field has an upload error.
         */
        hasError() {
            return this.uploadErrors.has(this.fieldAttribute);
        },
        /**
         * Return the first error for the field.
         */
        firstError() {
            if (this.hasError) {
                return this.uploadErrors.first(this.fieldAttribute);
            }
        },
        /**
         * The current label of the file field.
         */
        currentLabel() {
            return this.fileName || this.__('no file selected');
        },
        /**
         * The current label of the file field.
         */
        currentThumbnailLabel() {
            return this.thumbnailFileName || this.__('no file selected');
        },
        /**
         * The ID attribute to use for the file field.
         */
        idAttr() {
            return this.labelFor;
        },
        /**
         * The label attribute to use for the file field.
         */
        labelFor() {
            return `file-${this.currentField.attribute}-${Math.random().toString(36).substr(2)}`;
        },
        /**
         * Determine whether the field has a value.
         */
        hasValue() {
            return (
                Boolean(this.currentField.value || this.imageUrl)
                && !this.deleted
                && !this.missing
            );
        },

        /**
         * Determine whether the file field input should be editable.
         */
        isReadonly() {
            return Boolean(!!this.currentField.readonly);
        },

        /**
         * Determine whether the file field input should be shown.
         */
        shouldShowField() {
            return Boolean(!this.isReadonly);
        },
        /**
         * Determine whether the field should show the remove button.
         */
        shouldShowRemoveButton() {
            return Boolean(this.currentField.deletable && !this.isReadonly);
        },
        /**
         * Return the preview or thumbnail URL for the field.
         */
        imageUrl() {
            return this.currentField.thumbnailUrl;
        },
        /**
         * Return the preview URL for the field.
         */
        videoUrl() {
            return this.currentField.previewUrl;
        },
        /**
         * Determine the maximum width of the field.
         */
        maxWidth() {
            return this.currentField.maxWidth || 320;
        },
        /**
         * Determing if the field is a Vapor field.
         */
        isVaporField() {
            return this.currentField.component == 'vapor-file-field';
        },
        laruploadIsOn() {
            return this.currentField.laruploadIsOn;
        },
    },
    mounted() {
        this.currentField.fill = (formData) => {
            if (this.file && !this.isVaporField) {
                formData.append(this.currentField.attribute, this.file, this.fileName);
            }
            if (this.file && this.isVaporField) {
                formData.append(this.currentField.attribute, this.fileName);
                formData.append('vaporFile[key]', this.vaporFile.key);
                formData.append('vaporFile[uuid]', this.vaporFile.uuid);
                formData.append('vaporFile[filename]', this.vaporFile.filename);
                formData.append('vaporFile[extension]', this.vaporFile.extension);
            }
            if (this.thumbnailFile) {
                formData.append(`${this.currentField.attribute}_larupload_cover`, this.thumbnailFile);
            }
        };
    },
    methods: {
        /**
         * Respond to the file change
         */
        fileChange(event) {
            const path = event.target.value;
            const fileName = path.match(/[^\\/]*$/)[0];
            this.fileName = fileName;
            const extension = fileName.split('.').pop();
            this.file = this.$refs.fileField.files[0];
            if (this.isVaporField) {
                this.uploading = true;
                this.$emit('file-upload-started');
                Vapor.store(this.$refs.fileField.files[0], {
                    progress: (progress) => {
                        this.uploadProgress = Math.round(progress * 100);
                    },
                }).then((response) => {
                    this.vaporFile.key = response.key;
                    this.vaporFile.uuid = response.uuid;
                    this.vaporFile.filename = fileName;
                    this.vaporFile.extension = extension;
                    this.uploading = false;
                    this.uploadProgress = 0;
                    this.$emit('file-upload-finished');
                });
            }
        },
        /**
         * Respond to the thumbnail file change
         */
        thumbnailFileChange(event) {
            const path = event.target.value;
            const fileName = path.match(/[^\\/]*$/)[0];
            this.thumbnailFileName = fileName;
            this.thumbnailFile = this.$refs.thumbnailField.files[0];
        },
        /**
         * Confirm removal of the linked file
         */
        confirmRemoval() {
            this.removeModalOpen = true;
        },
        /**
         * Close the upload removal modal
         */
        closeRemoveModal() {
            this.removeModalOpen = false;
        },
        /**
         * Remove the linked file from storage
         */
        async removeFile() {
            this.uploadErrors = new Errors();
            const {
                resourceName,
                resourceId,
                relatedResourceName,
                relatedResourceId,
                viaRelationship,
            } = this;
            const {attribute} = this.currentField;
            const uri = this.viaRelationship
                ? `/nova-api/${resourceName}/${resourceId}/${relatedResourceName}/${relatedResourceId}/field/${attribute}?viaRelationship=${viaRelationship}`
                : `/nova-api/${resourceName}/${resourceId}/field/${attribute}`;
            try {
                await Nova.request().delete(uri);
                this.closeRemoveModal();
                this.deleted = true;
                this.$emit('file-deleted');
                Nova.success(this.__('The file was deleted!'));
            } catch (error) {
                this.closeRemoveModal();
                if (error.response.status == 422) {
                    this.uploadErrors = new Errors(error.response.data.errors);
                }
            }
        },

        download() {
            download(this.resourceName, this.resourceId, this.currentField.attribute)
        },
    },
};
</script>

<style lang="scss" scoped>
.video-wrapper {

    video {
        width: 600px;
        max-width: 100%;
        object-fit: cover;
        outline: none;
        border: none;
    }
}

</style>
