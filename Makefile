RUNTIME_TAG='polonaiz/spanner-extraction-test'
RUNTIME_DEV_TAG='polonaiz/spanner-extraction-test-dev'

build: \
	runtime-build \
	composer-install-in-runtime

update: \
	update-code \
	build

clean: \
	composer-clean

update-code:
	git reset --hard
	git pull

runtime-build:
	docker build \
		--tag ${RUNTIME_TAG} \
		./env/docker/runtime
	docker build \
		--tag ${RUNTIME_DEV_TAG} \
		./env/docker/runtime-dev

runtime-shell:
	docker run --rm -it --network 'host' \
 		${RUNTIME_TAG} bash

composer-install-in-runtime:
	docker run --rm \
		-v $(shell pwd):/opt/project \
		-v ~/.composer:/root/.composer \
 		${RUNTIME_TAG} composer -vvv install -d /opt/project

composer-update-in-runtime:
	docker run --rm \
		-v $(shell pwd):/opt/project \
		-v ~/.composer:/root/.composer \
 		${RUNTIME_TAG} composer -vvv update -d /opt/project

composer-clean:
	rm -rf ./vendor

spanner-emulator-start:
	docker run --rm -d -p 9010:9010 -p 9020:9020 \
		--name cloud-spanner-emulator \
		gcr.io/cloud-spanner-emulator/emulator

spanner-emulator-stop:
	docker stop cloud-spanner-emulator

emulator-setup-in-runtime:
	docker run --rm --network 'host' \
		-e SPANNER_EMULATOR_HOST=localhost:9010 \
		-v $(shell pwd):/opt/project \
 		${RUNTIME_TAG} php /opt/project/bin/setup

emulator-ingest-in-runtime:
	seq 0 10000 1000000 | parallel -j8 -n1 --verbose "docker run \
	--rm --network 'host' -e SPANNER_EMULATOR_HOST=localhost:9010 \
	-v $(shell pwd):/opt/project ${RUNTIME_TAG} php /opt/project/bin/ingest --begin={} --size=10000"

emulator-extract-in-runtime:
	docker run --rm --network 'host' \
		-e SPANNER_EMULATOR_HOST=localhost:9010 \
		-v $(shell pwd):/opt/project \
 		${RUNTIME_TAG} php /opt/project/bin/extract

cloud-application-default-login:
	gcloud auth application-default login
	# ~/.config/gcloud/application_default_credentials.json

cloud-setup-in-runtime:
	docker run --rm --network 'host' \
		-e GOOGLE_CLOUD_PROJECT=${GOOGLE_CLOUD_PROJECT} \
		-v ~/.config/gcloud:/root/.config/gcloud \
		-v $(shell pwd):/opt/project \
 		${RUNTIME_TAG} /opt/project/bin/setup

cloud-cleanup-in-runtime:
	docker run --rm --network 'host' \
		-e GOOGLE_CLOUD_PROJECT=${GOOGLE_CLOUD_PROJECT} \
		-v ~/.config/gcloud:/root/.config/gcloud \
		-v $(shell pwd):/opt/project \
 		${RUNTIME_TAG} php /opt/project/bin/cleanup

cloud-ingest-in-runtime:
	seq 0 5000 1000000 | parallel -j8 -n1 --verbose "docker run \
		--rm --network 'host' \
		-e GOOGLE_CLOUD_PROJECT=${GOOGLE_CLOUD_PROJECT} \
		-v ~/.config/gcloud:/root/.config/gcloud \
		-v $(shell pwd):/opt/project ${RUNTIME_TAG} \
		php /opt/project/bin/ingest --begin={} --size=5000"

cloud-extract-in-runtime:
	docker run --rm --network 'host' \
		-e GOOGLE_CLOUD_PROJECT=${GOOGLE_CLOUD_PROJECT} \
		-v ~/.config/gcloud:/root/.config/gcloud \
		-v $(shell pwd):/opt/project \
 		${RUNTIME_TAG} php /opt/project/bin/extract
